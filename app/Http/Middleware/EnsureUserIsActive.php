<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Deactivating a user must take effect immediately. Without this, someone whose account was
 * deactivated while signed in could keep using every route that has no role gate
 * (dashboard, "My tasks", finding updates, password change) until their session expired.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Contact an administrator.']);
        }

        return $next($request);
    }
}
