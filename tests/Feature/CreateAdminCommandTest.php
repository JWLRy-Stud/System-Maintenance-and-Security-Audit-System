<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_admin_and_logs_it(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Ada Admin')
            ->expectsQuestion('Email', 'ADA@Example.com')
            ->expectsQuestion('Password', 'Str0ngEnoughPass')
            ->expectsQuestion('Confirm password', 'Str0ngEnoughPass')
            ->assertExitCode(0);

        $admin = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('active', $admin->status);
        $this->assertTrue(Hash::check('Str0ngEnoughPass', $admin->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'subject_id' => $admin->id]);
    }

    public function test_name_and_email_can_be_given_as_options_but_the_password_is_always_asked(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'Bo', '--email' => 'bo@example.com'])
            ->expectsQuestion('Password', 'Str0ngEnoughPass')
            ->expectsQuestion('Confirm password', 'Str0ngEnoughPass')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'bo@example.com', 'role' => 'admin']);
    }

    public function test_weak_mismatched_or_duplicate_input_creates_nothing(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'W', '--email' => 'w@example.com'])
            ->expectsQuestion('Password', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertExitCode(1);

        $this->artisan('app:create-admin', ['--name' => 'M', '--email' => 'm@example.com'])
            ->expectsQuestion('Password', 'Str0ngEnoughPass')
            ->expectsQuestion('Confirm password', 'Different1Passw0rd')
            ->assertExitCode(1);

        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $this->artisan('app:create-admin', ['--name' => 'D', '--email' => $existing->email])
            ->expectsQuestion('Password', 'Str0ngEnoughPass')
            ->expectsQuestion('Confirm password', 'Str0ngEnoughPass')
            ->assertExitCode(1);

        $this->assertEquals(1, User::count()); // only the pre-existing user
    }
}
