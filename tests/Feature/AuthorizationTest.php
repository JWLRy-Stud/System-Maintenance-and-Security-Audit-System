<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $status = 'active'): User
    {
        return User::factory()->create(['role' => $role, 'status' => $status]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/assets')->assertRedirect('/login');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $u = $this->user('admin', 'inactive');
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_cannot_manage_assets_users_or_audits(): void
    {
        $staff = $this->user('staff');
        $this->actingAs($staff)->get('/assets/create')->assertForbidden();
        $this->actingAs($staff)->get('/users')->assertForbidden();
        $this->actingAs($staff)->get('/audits')->assertForbidden();
        $this->actingAs($staff)->get('/reports/logs')->assertForbidden();
    }

    public function test_admin_can_create_asset_and_duplicate_tag_is_rejected(): void
    {
        $admin = $this->user('admin');
        $data = ['asset_tag' => 'LAP-9', 'name' => 'Laptop', 'status' => 'active'];
        $this->actingAs($admin)->post('/assets', $data)->assertRedirect();
        $this->actingAs($admin)->post('/assets', $data)->assertSessionHasErrors('asset_tag');
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseHas('activity_logs', ['action' => 'asset.created']);
    }

    // ---- login lockout (found during the manual walkthrough: successful logins used to count towards the limit) ----

    public function test_many_successful_logins_from_one_ip_are_not_blocked(): void
    {
        foreach (['admin', 'auditor', 'technician', 'staff', 'admin', 'staff', 'auditor'] as $role) {
            $u = \App\Models\User::factory()->create(['role' => $role]);
            $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/');
            $this->post('/logout');
        }
    }

    public function test_five_failed_attempts_lock_that_account_even_for_the_right_password(): void
    {
        $u = \App\Models\User::factory()->create(['role' => 'staff']);
        $other = \App\Models\User::factory()->create(['role' => 'staff']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many failed attempts', session('errors')->first('email'));
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'outcome' => 'denied']);
        // another account from the same IP is unaffected
        $this->post('/login', ['email' => $other->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_successful_login_resets_the_failed_counter(): void
    {
        $u = \App\Models\User::factory()->create(['role' => 'staff']);
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/');
        $this->post('/logout');
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_forged_forwarded_for_header_cannot_dodge_the_login_lockout(): void
    {
        // With TRUSTED_PROXIES empty (phpunit.xml) X-Forwarded-For must be ignored, so the lockout key stays the same.
        $u = \App\Models\User::factory()->create(['role' => 'staff']);

        for ($i = 1; $i <= 5; $i++) {
            $this->withHeaders(['X-Forwarded-For' => "203.0.113.$i"])
                ->post('/login', ['email' => $u->email, 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->post('/login', ['email' => $u->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many failed attempts', session('errors')->first('email'));
        $this->assertGuest();
    }
}
