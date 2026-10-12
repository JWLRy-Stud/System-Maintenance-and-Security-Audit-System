<?php

namespace Tests\Feature;

use App\Models\AuditFinding;
use App\Models\SecurityAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAndTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_only_with_correct_current_password(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->put('/account/password', ['current_password' => 'wrong', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/account/password', ['current_password' => 'password', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newpassword1', $u->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_changed']);
    }

    public function test_assignee_sees_and_updates_only_their_own_findings(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        [$mine, $other] = [User::factory()->create(), User::factory()->create()];
        $audit = SecurityAudit::create(['title' => 'Q1', 'auditor_id' => $auditor->id, 'status' => 'in_progress']);
        $f = AuditFinding::create(['security_audit_id' => $audit->id, 'title' => 'Open port', 'severity' => 'high', 'status' => 'open', 'assigned_to' => $mine->id]);

        $this->actingAs($mine)->get('/my-findings')->assertOk()->assertSee('Open port');
        $this->actingAs($other)->get('/my-findings')->assertOk()->assertDontSee('Open port');
        $this->actingAs($other)->patch("/findings/{$f->id}", ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($mine)->patch("/findings/{$f->id}", ['status' => 'in_progress'])->assertSessionHasNoErrors();
    }
}
