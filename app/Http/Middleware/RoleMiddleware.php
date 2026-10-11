<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Server-side role gate: ->middleware('role:admin,auditor'). Admin always passes. */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active' && ($user->role === 'admin' || in_array($user->role, $roles, true)), 403);

        return $next($request);
    }
}
