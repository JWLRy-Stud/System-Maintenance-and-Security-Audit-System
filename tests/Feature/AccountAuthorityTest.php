<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountAuthorityTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role, string $status = 'active'): User
    {
        return User::factory()->create(['role' => $role, 'status' => $status]);
    }

    public function test_user_deactivated_while_signed_in_is_signed_out_on_next_request(): void
    {
        $u = $this->make('staff');
        $this->actingAs($u)->get('/')->assertOk();

        $u->update(['status' => 'inactive']);

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_update_a_finding_or_change_password(): void
    {
        $u = $this->make('staff', 'inactive');
        $this->actingAs($u)->put('/account/password', ['current_password' => 'password', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertRedirect('/login');
    }

    public function test_admin_can_open_the_edit_page_and_self_edit_locks_the_role(): void
    {
        $admin = $this->make('admin');
        $u = $this->make('staff');

        $this->actingAs($admin)->get("/users/{$u->id}/edit")->assertOk()->assertSee($u->email)->assertSee('Reset password');
        $this->actingAs($admin)->get("/users/{$admin->id}/edit")->assertOk()->assertSee('You cannot change your own role');
        $this->actingAs($admin)->get('/users')->assertOk()->assertSee('Edit');
    }

    public function test_admin_can_change_role_and_it_is_logged(): void
    {
        $admin = $this->make('admin');
        $u = $this->make('staff');

        $this->actingAs($admin)->put("/users/{$u->id}", ['name' => 'New Name', 'email' => $u->email, 'role' => 'technician'])->assertSessionHasNoErrors();

        $this->assertEquals('technician', $u->fresh()->role);
        $this->assertEquals('New Name', $u->fresh()->name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.role_changed', 'subject_id' => $u->id]);
    }

    public function test_admin_can_reset_password_and_blank_keeps_it(): void
    {
        $admin = $this->make('admin');
        $u = $this->make('staff');
        $old = $u->password;

        $this->actingAs($admin)->put("/users/{$u->id}", ['name' => $u->name, 'email' => $u->email, 'role' => 'staff', 'password' => ''])->assertSessionHasNoErrors();
        $this->assertEquals($old, $u->fresh()->password);

        $this->actingAs($admin)->put("/users/{$u->id}", ['name' => $u->name, 'email' => $u->email, 'role' => 'staff', 'password' => 'brandnewpass'])->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('brandnewpass', $u->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password_reset']);
    }

    public function test_non_admins_cannot_edit_users(): void
    {
        $u = $this->make('staff');
        foreach (['auditor', 'technician', 'staff'] as $role) {
            $this->actingAs($this->make($role))->get("/users/{$u->id}/edit")->assertForbidden();
            $this->actingAs($this->make($role))->put("/users/{$u->id}", ['name' => 'x', 'email' => 'x@example.com', 'role' => 'admin'])->assertForbidden();
        }
        $this->assertEquals('staff', $u->fresh()->role);
    }

    public function test_admin_cannot_change_own_role_or_deactivate_self(): void
    {
        $admin = $this->make('admin');
        $this->make('admin'); // a second admin exists, so only the self-rule applies

        $this->actingAs($admin)->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'staff'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->post("/users/{$admin->id}/toggle")->assertSessionHasErrors('status');
        $this->assertEquals('admin', $admin->fresh()->role);
    }

    public function test_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $service = app(UserAdminService::class);
        $only = $this->make('admin');
        $actor = $this->make('admin');
        $actor->update(['status' => 'inactive']); // actor is no longer an active admin; $only is the last one

        $this->assertThrows(fn () => $service->toggle($only, $actor), \Illuminate\Validation\ValidationException::class);
        $this->assertThrows(fn () => $service->update($only, $actor, ['name' => 'a', 'email' => $only->email, 'role' => 'staff']), \Illuminate\Validation\ValidationException::class);
        $this->assertEquals('active', $only->fresh()->status);
        $this->assertEquals('admin', $only->fresh()->role);

        $actor->update(['status' => 'active']); // now two admins: allowed
        $service->toggle($only, $actor);
        $this->assertEquals('inactive', $only->fresh()->status);
    }

    public function test_duplicate_email_is_rejected_on_edit(): void
    {
        $admin = $this->make('admin');
        $a = $this->make('staff');
        $b = $this->make('staff');
        $this->actingAs($admin)->put("/users/{$a->id}", ['name' => 'a', 'email' => $b->email, 'role' => 'staff'])->assertSessionHasErrors('email');
    }

    public function test_deactivation_deletes_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $u = $this->make('staff');
        $actor = $this->make('admin');
        DB::table('sessions')->insert(['id' => 's1', 'user_id' => $u->id, 'payload' => 'x', 'last_activity' => time()]);

        app(UserAdminService::class)->toggle($u, $actor);

        $this->assertDatabaseMissing('sessions', ['id' => 's1']);
    }

    public function test_dashboard_counts_are_scoped_and_auditor_sees_no_maintenance_cards(): void
    {
        $this->actingAs($this->make('auditor'))->get('/')->assertOk()->assertDontSee('Pending maintenance')->assertSee('Open findings');
        $this->actingAs($this->make('staff'))->get('/')->assertOk()->assertSee('Pending maintenance')->assertSee('My open findings');
    }
}
