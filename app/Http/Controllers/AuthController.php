<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin() { return view('login'); }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::attempt($cred + ['status' => 'active'])) {
            $request->session()->regenerate();
            ActivityLogService::record('auth.login');

            return redirect()->intended('/');
        }

        // BUG B-16 [OPEN]: failed logins are logged without the attempted email or the IP address, which makes
        //   brute-force attempts hard to investigate. FIX: store both (never the password) in a new activity_logs column or meta field.
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
