<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Rules for changing other people's accounts. Called only from admin-gated routes. */
class UserAdminService
{
    /** Edit name/email/role and optionally reset the password. */
    public function update(User $target, User $actor, array $data): User
    {
        return DB::transaction(function () use ($target, $actor, $data) {
            $newRole = $data['role'];
            $roleChanged = $newRole !== $target->role;

            if ($roleChanged && $target->id === $actor->id) {
                $this->refuse('role', 'You cannot change your own role. Ask another administrator.');
            }
            if ($roleChanged && $target->role === 'admin') {
                $this->guardLastAdmin($target, 'role', 'The last active administrator cannot be demoted.');
            }

            $payload = ['name' => $data['name'], 'email' => $data['email']];
            $passwordReset = ! empty($data['password']);
            if ($passwordReset) {
                $payload['password'] = $data['password']; // hashed by the model cast
            }
            $target->fill($payload);
            $target->forceFill(['role' => $newRole]); // role is not mass-assignable (see User::$fillable)
            $target->save();

            ActivityLogService::record('user.updated', $target);
            if ($roleChanged) {
                ActivityLogService::record('user.role_changed', $target);
            }
            if ($passwordReset) {
                ActivityLogService::record('user.password_reset', $target);
            }
            if ($roleChanged || $passwordReset) {
                $this->revokeSessions($target); // old authority / old password stops working immediately
            }

            return $target;
        });
    }

    /** Deactivate or reactivate (accounts are never deleted so history is preserved). */
    public function toggle(User $target, User $actor): User
    {
        return DB::transaction(function () use ($target, $actor) {
            $deactivating = $target->status === 'active';

            if ($deactivating && $target->id === $actor->id) {
                $this->refuse('status', 'You cannot deactivate yourself.');
            }
            if ($deactivating && $target->role === 'admin') {
                $this->guardLastAdmin($target, 'status', 'The last active administrator cannot be deactivated.');
            }

            $target->forceFill(['status' => $deactivating ? 'inactive' : 'active'])->save(); // status is not mass-assignable
            ActivityLogService::record('user.status_changed', $target);

            if ($deactivating) {
                $this->revokeSessions($target);
            }

            return $target;
        });
    }

    /** Refuse if $target is an active admin and the only one left. */
    private function guardLastAdmin(User $target, string $field, string $message): void
    {
        if ($target->status !== 'active') {
            return;
        }
        $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->pluck('id');
        if ($admins->count() <= 1) {
            $this->refuse($field, $message);
        }
    }

    /** Sign the user out everywhere (database sessions) and invalidate "remember me". */
    public function revokeSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }

    private function refuse(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
