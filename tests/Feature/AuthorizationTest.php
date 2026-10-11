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
}
