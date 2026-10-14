<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hosting platforms terminate HTTPS at a proxy; trust it so URLs/redirects use https.
        // BUG B-10 [OPEN] (security, high if exposed directly): trusting every proxy ('*') lets a client send a forged
        //   X-Forwarded-For header, so the per-IP login throttle (throttle:5,1 in routes/web.php) can be bypassed.
        //   FIX: read the real proxy IPs/CIDRs from an env var (e.g. TRUSTED_PROXIES) instead of '*'. Check docs/DEPLOYMENT.md first.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['role' => RoleMiddleware::class, 'active' => EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
