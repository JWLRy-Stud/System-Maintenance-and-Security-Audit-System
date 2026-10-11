<?php

namespace Tests\Feature;

use App\Models\AuditFinding;
use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_records_finding_and_follows_valid_transitions(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = SecurityAudit::create(['title' => 'Q1', 'auditor_id' => $auditor->id, 'status' => 'in_progress']);

        $this->actingAs($auditor)->post("/audits/{$audit->id}/findings", ['title' => 'Weak passwords', 'severity' => 'high'])->assertSessionHasNoErrors();
        $f = AuditFinding::firstOrFail();

        $this->actingAs($auditor)->patch("/findings/{$f->id}", ['status' => 'resolved'])->assertSessionHasErrors('status'); // open -> resolved not allowed
        $this->actingAs($auditor)->patch("/findings/{$f->id}", ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->actingAs($auditor)->patch("/findings/{$f->id}", ['status' => 'resolved'])->assertSessionHasNoErrors();
        $this->assertNotNull($f->fresh()->resolved_at);
    }

    public function test_cannot_add_findings_to_completed_audit_or_other_auditors_audit(): void
    {
        [$a, $b] = [User::factory()->create(['role' => 'auditor']), User::factory()->create(['role' => 'auditor'])];
        $audit = SecurityAudit::create(['title' => 'Q1', 'auditor_id' => $a->id, 'status' => 'in_progress']);

        $this->actingAs($b)->post("/audits/{$audit->id}/findings", ['title' => 'x', 'severity' => 'low'])->assertForbidden();
        $this->actingAs($a)->post("/audits/{$audit->id}/complete");
        $this->actingAs($a)->post("/audits/{$audit->id}/findings", ['title' => 'x', 'severity' => 'low'])->assertSessionHasErrors('title');
    }
}
