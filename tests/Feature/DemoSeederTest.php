<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_the_four_demo_users_with_their_roles_outside_production(): void
    {
        (new DatabaseSeeder)->run();

        foreach (['admin', 'technician', 'auditor', 'staff'] as $role) {
            $user = User::where('email', "{$role}@example.com")->first();
            $this->assertNotNull($user, "{$role} demo user missing");
            $this->assertEquals($role, $user->role);
            $this->assertEquals('active', $user->status);
        }

        (new DatabaseSeeder)->run(); // running twice must not duplicate anything
        $this->assertEquals(4, User::count());
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new DatabaseSeeder)->run();

        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Asset::count());
    }
}
