<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /** Lock the form after this many WRONG current passwords per user within a minute (like the login lockout). */
    private const MAX_FAILED_CHECKS = 5;

    public function edit() { return view('account.password'); }

    public function update(Request $request)
    {
        // Someone holding a hijacked session must not be able to brute-force the current password here without ever
        // hitting the login lockout. Keyed on the user id; only failed checks count and a success clears the counter.
        $key = 'password-change|'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_CHECKS)) {
            ActivityLogService::record('auth.password_changed', $request->user(), 'denied', null, 'locked: too many wrong current passwords');
            throw ValidationException::withMessages(['current_password' => 'Too many failed attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        try {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', User::passwordRule(), 'confirmed', 'different:current_password'],
            ]);
        } catch (ValidationException $e) {
            if ($request->filled('current_password') && array_key_exists('current_password', $e->errors())) {
                RateLimiter::hit($key, 60);
                ActivityLogService::record('auth.password_changed', $request->user(), 'failed', null, 'wrong current password');
            }

            throw $e;
        }
        RateLimiter::clear($key);

        $request->user()->fill(['password' => $request->password]); // hashed by the model cast
        $request->user()->forceFill(['must_change_password' => false])->save();
        Auth::logoutOtherDevices($request->password); // other sessions/devices fail the 'auth.session' check from now on
        ActivityLogService::record('auth.password_changed', $request->user());

        return back()->with('ok', 'Password updated.');
    }
}
