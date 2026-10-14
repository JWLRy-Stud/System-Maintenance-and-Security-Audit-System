<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * A password an admin chose (new account or reset) is known to the admin, so its owner must replace it first.
 * Until then only the change-password page and logout are reachable.
 */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs('account.password', 'account.password.update', 'logout')) {
            return redirect()->route('account.password')->with('warn', 'Choose your own password before you continue.');
        }

        return $next($request);
    }
}
