<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hosting platforms terminate HTTPS at a proxy; trust ONLY that proxy so URLs/redirects use https.
        // Trusting every proxy would let a client forge X-Forwarded-For and dodge the login lockout
        // (its key is email|IP, see AuthController::login). TRUSTED_PROXIES is a comma-separated list of the
        // host's proxy IPs/CIDRs. Unset/empty = trust none (correct when the app is reached directly).
        // '*' trusts every proxy: only use it if the app can ONLY be reached through your host's proxy.
        // Set it as a real host environment variable: env() does not read .env once `config:cache` has run.
        // BUG B-27 [OPEN]: only the "nothing trusted" default is covered by a test. FIX: add a test that builds the app with
        //   TRUSTED_PROXIES set and checks X-Forwarded-For is honoured from that proxy IP only (and ignored from any other).
        $trusted = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(at: match (true) {
            $trusted === '' => [],
            $trusted === '*' => '*',
            default => array_values(array_filter(array_map('trim', explode(',', $trusted)))),
        });
        $middleware->alias(['role' => RoleMiddleware::class, 'active' => EnsureUserIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every 403 (role middleware, policies, abort(403)) lands in the activity log as 'denied'. Returning null
        // lets Laravel render the normal 403 page.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                ActivityLogService::recordForbidden($request);
            }

            return null;
        });
    })->create();
