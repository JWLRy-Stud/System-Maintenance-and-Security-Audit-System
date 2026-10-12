<?php

namespace Tests\Feature;

use App\Models\AuditCheck;
use App\Models\ChecklistTemplate;
use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    private function startAudit(User $auditor): SecurityAudit
    {
        $this->actingAs($auditor)->post('/checklists', ['name' => 'Baseline', 'questions' => "Firewall on?\n\n  Backups tested?  \n"])->assertSessionHasNoErrors();
        $tpl = ChecklistTemplate::firstOrFail();
        $this->actingAs($auditor)->post('/audits', ['title' => 'Q1', 'checklist_template_id' => $tpl->id])->assertSessionHasNoErrors();

        return SecurityAudit::firstOrFail();
    }

    public function test_template_lines_become_snapshot_checks_on_new_audit(): void
    {
        $audit = $this->startAudit(User::factory()->create(['role' => 'auditor']));

        $this->assertEquals(['Firewall on?', 'Backups tested?'], $audit->checks->pluck('question')->all());
        $this->assertEquals(['pending', 'pending'], $audit->checks->pluck('result')->all());
    }

    public function test_cannot_complete_audit_with_pending_checks(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = $this->startAudit($auditor);

        $this->actingAs($auditor)->post("/audits/{$audit->id}/complete")->assertSessionHasErrors('status');
        $this->assertEquals('in_progress', $audit->fresh()->status);

        foreach ($audit->checks as $c) {
            $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'pass'])->assertSessionHasNoErrors();
        }
        $this->actingAs($auditor)->post("/audits/{$audit->id}/complete")->assertSessionHasNoErrors();
        $this->assertEquals('completed', $audit->fresh()->status);
    }

    public function test_failing_a_check_raises_one_linked_finding_and_requires_severity(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $check = $this->startAudit($auditor)->checks->first();

        $this->actingAs($auditor)->patch("/checks/{$check->id}", ['result' => 'fail'])->assertSessionHasErrors('severity');
        $this->actingAs($auditor)->patch("/checks/{$check->id}", ['result' => 'fail', 'severity' => 'high', 'notes' => 'Disabled on 3 PCs'])->assertSessionHasNoErrors();
        $this->actingAs($auditor)->patch("/checks/{$check->id}", ['result' => 'fail', 'severity' => 'high'])->assertSessionHasNoErrors(); // re-save

        $this->assertDatabaseCount('audit_findings', 1);
        $this->assertNotNull($check->fresh()->finding_id);
    }

    public function test_other_auditor_and_staff_cannot_answer_checks_and_locked_after_completion(): void
    {
        $owner = User::factory()->create(['role' => 'auditor']);
        $check = $this->startAudit($owner)->checks->first();

        $this->actingAs(User::factory()->create(['role' => 'auditor']))->patch("/checks/{$check->id}", ['result' => 'pass'])->assertForbidden();
        $this->actingAs(User::factory()->create())->patch("/checks/{$check->id}", ['result' => 'pass'])->assertForbidden();

        AuditCheck::query()->update(['result' => 'pass']);
        $this->actingAs($owner)->post('/audits/'.$check->security_audit_id.'/complete');
        $this->actingAs($owner)->patch("/checks/{$check->id}", ['result' => 'fail', 'severity' => 'low'])->assertSessionHasErrors('result');
    }
}
