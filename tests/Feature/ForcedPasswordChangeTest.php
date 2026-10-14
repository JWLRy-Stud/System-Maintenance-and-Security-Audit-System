<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_created_by_an_admin_must_replace_the_password_before_anything_else(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post('/users', ['name' => 'New Hire', 'email' => 'hire@example.com', 'password' => 'Str0ngEnoughPass', 'role' => 'staff'])
            ->assertSessionHasNoErrors();

        $hire = User::where('email', 'hire@example.com')->firstOrFail();
        $this->assertTrue($hire->must_change_password);

        $this->flushSession(); // the admin's session still holds the admin's password hash; 'auth.session' would sign the new user out
        $this->actingAs($hire);
        $this->get('/')->assertRedirect(route('account.password'));
        $this->get('/assets')->assertRedirect(route('account.password'));
        $this->get('/account/password')->assertOk();

        $this->put('/account/password', ['current_password' => 'Str0ngEnoughPass', 'password' => 'MyOwnPassw0rd1', 'password_confirmation' => 'MyOwnPassw0rd1'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($hire->fresh()->must_change_password);
        $this->get('/')->assertOk();
    }

    public function test_the_flagged_user_can_still_log_out(): void
    {
        $u = User::factory()->create(['role' => 'staff']);
        $u->forceFill(['must_change_password' => true])->save();

        $this->actingAs($u)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_an_admin_password_reset_flags_the_target_but_not_the_admin_resetting_their_own(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        /** @var User $other */
        $other = User::factory()->create(['role' => 'staff']);

        $this->actingAs($admin)->put("/users/{$other->id}", ['name' => $other->name, 'email' => $other->email, 'role' => 'staff', 'password' => 'ResetByAdmin12'])->assertSessionHasNoErrors();
        $this->assertTrue($other->fresh()->must_change_password);

        $this->actingAs($admin)->put("/users/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'password' => 'MyOwnAdminPass12'])->assertSessionHasNoErrors();
        $this->assertFalse($admin->fresh()->must_change_password);
    }

    public function test_users_who_were_never_flagged_are_not_redirected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))->get('/')->assertOk();
    }
}
