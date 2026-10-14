<?php

namespace Tests\Unit;

use App\Support\TrustedProxies;
use PHPUnit\Framework\TestCase;

class TrustedProxiesTest extends TestCase
{
    public function test_empty_means_trust_none(): void
    {
        $this->assertSame([], TrustedProxies::parse(''));
        $this->assertSame([], TrustedProxies::parse('  , ,'));
    }

    public function test_star_means_trust_every_proxy(): void
    {
        $this->assertSame('*', TrustedProxies::parse(' * '));
    }

    public function test_a_list_is_trimmed_and_cleaned(): void
    {
        $this->assertSame(['10.0.0.5', '192.168.0.0/16'], TrustedProxies::parse(' 10.0.0.5 , ,192.168.0.0/16,'));
    }
}
