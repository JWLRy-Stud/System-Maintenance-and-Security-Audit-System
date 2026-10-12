<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function edit() { return view('account.password'); }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $request->password]); // hashed by the model cast
        ActivityLogService::record('auth.password_changed', $request->user());

        return back()->with('ok', 'Password updated.');
    }
}
