<?php

namespace Tests\Feature;

use App\Models\AuditCheck;
use App\Models\AuditFinding;
use App\Models\ChecklistTemplate;
use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAuthorityTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role, string $status = 'active'): User
    {
        return User::factory()->create(['role' => $role, 'status' => $status]);
    }

    private function finding(User $auditor, ?User $assignee, string $status = 'open'): AuditFinding
    {
        $audit = SecurityAudit::create(['title' => 'Q1', 'auditor_id' => $auditor->id, 'status' => 'in_progress']);

        return AuditFinding::create(['security_audit_id' => $audit->id, 'title' => 'F-'.uniqid(), 'severity' => 'high', 'status' => $status, 'assigned_to' => $assignee?->id]);
    }

    public function test_assignee_cannot_accept_risk_or_reopen_but_can_resolve(): void
    {
        $auditor = $this->make('auditor');
        $staff = $this->make('staff');
        $f = $this->finding($auditor, $staff);

        $this->actingAs($staff)->patch("/findings/{$f->id}", ['status' => 'accepted_risk'])->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['action' => 'finding.accepted_risk', 'outcome' => 'denied']);
        $this->actingAs($staff)->patch("/findings/{$f->id}", ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->patch("/findings/{$f->id}", ['status' => 'open'])->assertForbidden(); // reopen
        $this->actingAs($staff)->patch("/findings/{$f->id}", ['status' => 'resolved'])->assertSessionHasNoErrors();
        $this->assertEquals('resolved', $f->fresh()->status);
    }

    public function test_audit_owner_and_admin_can_accept_risk_but_other_auditor_cannot(): void
    {
        $owner = $this->make('auditor');
        $f = $this->finding($owner, null);

        $this->actingAs($this->make('auditor'))->patch("/findings/{$f->id}", ['status' => 'accepted_risk'])->assertForbidden();
        $this->actingAs($owner)->patch("/findings/{$f->id}", ['status' => 'accepted_risk'])->assertSessionHasNoErrors();
        $this->assertEquals('accepted_risk', $f->fresh()->status);

        $g = $this->finding($owner, null);
        $this->actingAs($this->make('admin'))->patch("/findings/{$g->id}", ['status' => 'accepted_risk'])->assertSessionHasNoErrors();
    }

    public function test_owner_can_assign_finding_and_it_appears_in_my_tasks(): void
    {
        $owner = $this->make('auditor');
        $dev = $this->make('technician');
        $f = $this->finding($owner, null);

        $this->actingAs($owner)->patch("/findings/{$f->id}/assignment", ['assigned_to' => $dev->id, 'due_date' => '2030-01-31'])->assertSessionHasNoErrors();

        $this->assertEquals($dev->id, $f->fresh()->assigned_to);
        $this->assertEquals('2030-01-31', $f->fresh()->due_date->toDateString());
        $this->actingAs($dev)->get('/my-findings')->assertSee($f->title);
        $this->assertDatabaseHas('activity_logs', ['action' => 'finding.assigned']);
    }

    public function test_only_owner_or_admin_can_assign_and_inactive_or_closed_are_rejected(): void
    {
        $owner = $this->make('auditor');
        $dev = $this->make('technician');
        $gone = $this->make('technician', 'inactive');
        $f = $this->finding($owner, null);

        $this->actingAs($this->make('auditor'))->patch("/findings/{$f->id}/assignment", ['assigned_to' => $dev->id])->assertForbidden();
        $this->actingAs($this->make('staff'))->patch("/findings/{$f->id}/assignment", ['assigned_to' => $dev->id])->assertForbidden();
        $this->actingAs($dev)->patch("/findings/{$f->id}/assignment", ['assigned_to' => $dev->id])->assertForbidden();
        $this->actingAs($owner)->patch("/findings/{$f->id}/assignment", ['assigned_to' => $gone->id])->assertSessionHasErrors('assigned_to');

        $closed = $this->finding($owner, null, 'resolved');
        $this->actingAs($owner)->patch("/findings/{$closed->id}/assignment", ['assigned_to' => $dev->id])->assertSessionHasErrors('assigned_to');
        $this->assertNull($f->fresh()->assigned_to);
    }

    public function test_new_finding_cannot_be_assigned_to_inactive_user(): void
    {
        $auditor = $this->make('auditor');
        $gone = $this->make('staff', 'inactive');
        $audit = SecurityAudit::create(['title' => 'Q', 'auditor_id' => $auditor->id, 'status' => 'in_progress']);

        $this->actingAs($auditor)->post("/audits/{$audit->id}/findings", ['title' => 'x', 'severity' => 'low', 'assigned_to' => $gone->id])->assertSessionHasErrors('assigned_to');
    }

    public function test_non_owner_auditor_sees_audit_read_only(): void
    {
        $owner = $this->make('auditor');
        $audit = SecurityAudit::create(['title' => 'Owned audit', 'auditor_id' => $owner->id, 'status' => 'in_progress']);

        $this->actingAs($this->make('auditor'))->get("/audits/{$audit->id}")->assertOk()->assertDontSee('Complete audit')->assertDontSee('Record a finding');
        $this->actingAs($owner)->get("/audits/{$audit->id}")->assertOk()->assertSee('Complete audit')->assertSee('Record a finding');
        $this->actingAs($this->make('staff'))->get("/audits/{$audit->id}")->assertForbidden();
    }

    public function test_finding_buttons_match_what_the_viewer_may_do(): void
    {
        $owner = $this->make('auditor');
        $staff = $this->make('staff');
        $f = $this->finding($owner, $staff);

        $this->actingAs($staff)->get('/my-findings')->assertSee('Start work')->assertDontSee('Accept risk');
        $audit = $f->audit;
        $this->actingAs($owner)->get("/audits/{$audit->id}")->assertSee('Accept risk')->assertSee('Reassign');
    }

    // ---- fail -> pass closes the linked finding -------------------------------------------------------------

    private function check(User $auditor): AuditCheck
    {
        $audit = SecurityAudit::create(['title' => 'Q', 'auditor_id' => $auditor->id, 'status' => 'in_progress']);

        return $audit->checks()->create(['question' => 'Firewall on?', 'position' => 0]);
    }

    public function test_changing_fail_to_pass_resolves_the_linked_finding(): void
    {
        $auditor = $this->make('auditor');
        $c = $this->check($auditor);

        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'fail', 'severity' => 'high'])->assertSessionHasNoErrors();
        $finding = AuditFinding::firstOrFail();
        $this->assertEquals('open', $finding->status);

        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'pass'])->assertSessionHasNoErrors();

        $this->assertEquals('resolved', $finding->fresh()->status);
        $this->assertNotNull($finding->fresh()->resolved_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'finding.auto_resolved']);
        $this->assertDatabaseCount('audit_findings', 1);
    }

    public function test_failing_again_reopens_but_accepted_risk_is_left_alone(): void
    {
        $auditor = $this->make('auditor');
        $c = $this->check($auditor);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'fail', 'severity' => 'high']);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'pass']);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'fail', 'severity' => 'critical']);

        $f = AuditFinding::firstOrFail();
        $this->assertEquals('open', $f->status);
        $this->assertNull($f->resolved_at);
        $this->assertEquals('critical', $f->severity);
        $this->assertDatabaseCount('audit_findings', 1);

        $f->update(['status' => 'accepted_risk']);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'pass']);
        $this->assertEquals('accepted_risk', $f->fresh()->status);
    }

    public function test_resaving_fail_updates_severity_of_open_finding(): void
    {
        $auditor = $this->make('auditor');
        $c = $this->check($auditor);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'fail', 'severity' => 'low']);
        $this->actingAs($auditor)->patch("/checks/{$c->id}", ['result' => 'fail', 'severity' => 'high', 'notes' => 'worse']);

        $f = AuditFinding::firstOrFail();
        $this->assertEquals('high', $f->severity);
        $this->assertEquals('worse', $f->description);
    }

    public function test_findings_csv_neutralises_formula_prefixes_including_tab(): void
    {
        $auditor = $this->make('auditor');
        $audit = SecurityAudit::create(['title' => "\t=cmd", 'auditor_id' => $auditor->id, 'status' => 'in_progress']);
        AuditFinding::create(['security_audit_id' => $audit->id, 'title' => '=1+1', 'severity' => 'low', 'status' => 'open']);

        $csv = $this->actingAs($auditor)->get('/reports/findings.csv')->streamedContent();

        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'\t=cmd", $csv);
    }
}
