<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function edit() { return view('account.password'); }

    public function update(Request $request)
    {
        // BUG B-26 [OPEN]: no rate limit on 'current_password' guesses. Someone holding a hijacked session can brute-force
        //   the current password here without ever hitting the login lockout. FIX: RateLimiter keyed on user id (like AuthController::login), failures only.
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', User::passwordRule(), 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $request->password]); // hashed by the model cast
        Auth::logoutOtherDevices($request->password); // other sessions/devices fail the 'auth.session' check from now on
        ActivityLogService::record('auth.password_changed', $request->user());

        return back()->with('ok', 'Password updated.');
    }
}
