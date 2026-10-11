<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index() { return view('users.index', ['users' => User::orderBy('name')->paginate(20)]); }

    public function create() { return view('users.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(User::ROLES)],
        ]);
        $user = User::create($data + ['status' => 'active']);
        ActivityLogService::record('user.created', $user);

        return redirect()->route('users.index')->with('ok', 'User created.');
    }

    /** Deactivate instead of deleting so maintenance/audit history is preserved. */
    public function toggle(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot deactivate yourself.');
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        ActivityLogService::record('user.status_changed', $user);

        return back()->with('ok', 'User status updated.');
    }
}
