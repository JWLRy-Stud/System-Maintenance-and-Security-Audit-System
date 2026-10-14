<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_records_the_attempted_email_and_ip_but_never_the_password(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->post('/login', ['email' => 'Someone@Example.com', 'password' => 'my-secret-guess'])
            ->assertSessionHasErrors('email');

        $log = ActivityLog::where('action', 'auth.login')->where('outcome', 'failed')->firstOrFail();
        $this->assertEquals('198.51.100.7', $log->ip_address);
        $this->assertStringContainsString('Someone@Example.com', $log->detail);
        $this->assertFalse(ActivityLog::where('detail', 'like', '%my-secret-guess%')->exists());
    }

    public function test_locked_out_login_is_logged_as_denied_with_the_email(): void
    {
        $u = User::factory()->create(['role' => 'staff']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'password']);

        $log = ActivityLog::where('action', 'auth.login')->where('outcome', 'denied')->firstOrFail();
        $this->assertStringContainsString($u->email, $log->detail);
        $this->assertNotNull($log->ip_address);
    }

    public function test_any_403_is_logged_as_denied_with_the_actor_and_route(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/users')->assertForbidden();        // role middleware
        $this->actingAs($staff)->get('/checklists')->assertForbidden();   // another role gate

        $this->assertDatabaseHas('activity_logs', ['action' => 'access.denied', 'outcome' => 'denied', 'actor_id' => $staff->id, 'detail' => 'GET users']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'access.denied', 'outcome' => 'denied', 'actor_id' => $staff->id, 'detail' => 'GET checklists']);
    }

    public function test_a_denial_that_was_already_logged_is_not_logged_twice(): void
    {
        ActivityLogService::record('maintenance.completed', null, 'denied'); // what the services do before abort(403)
        ActivityLogService::recordForbidden(request());

        $this->assertDatabaseMissing('activity_logs', ['action' => 'access.denied']);
    }

    public function test_each_request_gets_its_own_request_id(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/users');
        $this->actingAs($staff)->get('/users');

        $ids = ActivityLog::where('action', 'access.denied')->pluck('request_id');
        $this->assertCount(2, $ids);
        $this->assertNotContains(null, $ids->all());
        $this->assertCount(2, $ids->unique());
    }
}
