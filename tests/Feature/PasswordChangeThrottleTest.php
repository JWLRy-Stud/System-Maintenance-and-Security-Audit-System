<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordChangeThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function change(string $current, string $new = 'NewPassword12')
    {
        return $this->put('/account/password', ['current_password' => $current, 'password' => $new, 'password_confirmation' => $new]);
    }

    public function test_five_wrong_current_passwords_lock_the_form_even_for_the_right_one(): void
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'staff']);
        $this->actingAs($u);

        for ($i = 0; $i < 5; $i++) {
            $this->change('wrong-guess')->assertSessionHasErrors('current_password');
        }

        $this->change('password')->assertSessionHasErrors('current_password');
        $this->assertStringContainsString('Too many failed attempts', session('errors')->first('current_password'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $u->fresh()->password), 'password must be unchanged while locked');
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_changed', 'outcome' => 'failed', 'actor_id' => $u->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_changed', 'outcome' => 'denied', 'actor_id' => $u->id]);
    }

    public function test_the_lock_is_per_user(): void
    {
        /** @var User $a */
        $a = User::factory()->create(['role' => 'staff']);
        /** @var User $b */
        $b = User::factory()->create(['role' => 'staff']);

        $this->actingAs($a);
        for ($i = 0; $i < 5; $i++) {
            $this->change('wrong-guess');
        }

        $this->actingAs($b);
        $this->change('password')->assertSessionHasNoErrors();
    }

    public function test_a_successful_change_resets_the_failed_counter(): void
    {
        /** @var User $u */
        $u = User::factory()->create(['role' => 'staff']);
        $this->actingAs($u);

        for ($i = 0; $i < 4; $i++) {
            $this->change('wrong-guess')->assertSessionHasErrors('current_password');
        }
        $this->change('password')->assertSessionHasNoErrors(); // current is now NewPassword12

        for ($i = 0; $i < 4; $i++) {
            $this->change('wrong-guess', 'AnotherPass345')->assertSessionHasErrors('current_password');
        }
        $this->change('NewPassword12', 'AnotherPass345')->assertSessionHasNoErrors();
    }
}
