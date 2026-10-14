<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Boots the whole app with TRUSTED_PROXIES set (phpunit.xml leaves it empty for every other test). */
class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        // bootstrap/app.php reads the variable while the app is being built, so it must be set before that.
        $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = '10.0.0.5, 192.168.0.0/16';

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = ''; // back to phpunit.xml's value for the next test class
    }

    /** A failed login stores request->ip(), which shows whether X-Forwarded-For was believed. */
    private function failedLoginIp(string $remoteAddr, string $forwardedFor): ?string
    {
        $u = User::factory()->create(['role' => 'staff']);

        $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr])
            ->withHeaders(['X-Forwarded-For' => $forwardedFor])
            ->post('/login', ['email' => $u->email, 'password' => 'wrong']);

        return \App\Models\ActivityLog::where('action', 'auth.login')->where('outcome', 'failed')->latest('id')->value('ip_address');
    }

    public function test_forwarded_for_is_honoured_from_a_listed_proxy_ip(): void
    {
        $this->assertSame('203.0.113.9', $this->failedLoginIp('10.0.0.5', '203.0.113.9'));
    }

    public function test_forwarded_for_is_honoured_from_a_proxy_inside_a_listed_cidr(): void
    {
        $this->assertSame('203.0.113.10', $this->failedLoginIp('192.168.4.4', '203.0.113.10'));
    }

    public function test_forwarded_for_is_ignored_from_any_other_address(): void
    {
        $this->assertSame('198.51.100.77', $this->failedLoginIp('198.51.100.77', '203.0.113.11'));
        $this->assertSame('10.0.0.6', $this->failedLoginIp('10.0.0.6', '203.0.113.12')); // next to a listed IP, but not listed
    }
}
