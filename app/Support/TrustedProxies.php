<?php

namespace App\Support;

/** Turns the TRUSTED_PROXIES env value into what Laravel's trustProxies(at: ...) expects. */
class TrustedProxies
{
    /**
     * '' => [] (trust none), '*' => '*' (trust every proxy), otherwise the comma-separated IPs/CIDRs as a clean list.
     *
     * @return array<int, string>|string
     */
    public static function parse(string $value): array|string
    {
        $value = trim($value);

        if ($value === '*') {
            return '*';
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $p) => $p !== ''));
    }
}
