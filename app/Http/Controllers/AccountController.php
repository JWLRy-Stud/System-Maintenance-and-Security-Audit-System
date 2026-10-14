<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function edit() { return view('account.password'); }

    public function update(Request $request)
    {
        // BUG B-12 [OPEN]: changing the password does not sign out this user's OTHER sessions.
        //   FIX: add the 'auth.session' middleware to the auth group and call Auth::logoutOtherDevices($request->password) below.
        // BUG B-15 [OPEN]: password rule is only min:8. FIX: one shared Password::min(10)->mixedCase()->numbers() rule
        //   used here and in UserController (store/update).
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $request->password]); // hashed by the model cast
        ActivityLogService::record('auth.password_changed', $request->user());

        return back()->with('ok', 'Password updated.');
    }
}
