<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Lock out after this many FAILED attempts per email + IP within a minute (successful logins are never counted). */
    private const MAX_FAILED_ATTEMPTS = 5;

    public function showLogin() { return view('login'); }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);

        // Per account + IP, failures only. The old route-level throttle:5,1 counted every login (even successful ones), so
        // five people on one office IP, or one person trying the four demo roles, were locked out for a minute.
        $key = Str::lower($cred['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            ActivityLogService::record('auth.login', null, 'denied', null);
            throw ValidationException::withMessages(['email' => 'Too many failed attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        if (Auth::attempt($cred + ['status' => 'active'])) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            ActivityLogService::record('auth.login');

            return redirect()->intended('/');
        }

        // BUG B-16 [OPEN]: failed logins are logged without the attempted email or the IP address, which makes
        //   brute-force attempts hard to investigate. FIX: store both (never the password) in a new activity_logs column or meta field.
        RateLimiter::hit($key, 60);
        ActivityLogService::record('auth.login', null, 'failed', null);

        return back()->withErrors(['email' => 'Invalid credentials or inactive account.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        ActivityLogService::record('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
