<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index() { return view('users.index', ['users' => User::orderBy('name')->paginate(20)]); }

    public function create() { return view('users.create'); }

    public function store(Request $request)
    {
        // BUG B-22 [OPEN, unverified]: email is not lowercased before the unique check. MySQL's default collation is
        //   case-insensitive so duplicates are probably still caught; confirm, or normalise with prepareForValidation.
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', User::passwordRule()],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);
        $user = new User(Arr::only($data, ['name', 'email', 'password']));
        $user->forceFill(['role' => $data['role'], 'status' => 'active', 'must_change_password' => true])->save(); // role/status are not mass-assignable; the admin knows this password, so the user must replace it
        ActivityLogService::record('user.created', $user);

        return redirect()->route('users.index')->with('ok', 'User created.');
    }

    public function edit(User $user)
    {
        Gate::authorize('manage', $user);

        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user, UserAdminService $service)
    {
        Gate::authorize('manage', $user);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'string', User::passwordRule()], // leave blank to keep the current password
        ]);

        $service->update($user, $request->user(), $data);

        return redirect()->route('users.index')->with('ok', 'User updated.');
    }

    /** Deactivate instead of deleting so maintenance/audit history is preserved. */
    public function toggle(Request $request, User $user, UserAdminService $service)
    {
        Gate::authorize('manage', $user);
        $service->toggle($user, $request->user());

        return back()->with('ok', 'User status updated.');
    }
}
