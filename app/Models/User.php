<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rules\Password;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'technician', 'auditor', 'staff'];

    // 'role' and 'status' are deliberately NOT fillable: a stray $user->update($request->all()) must never be able to
    // grant admin or reactivate an account. Set them explicitly with forceFill() (UserAdminService, UserController::store,
    // DatabaseSeeder). Factories ignore this guard, so User::factory()->create(['role' => ...]) still works in tests.
    protected $fillable = ['name', 'email', 'password'];

    /** The ONE password policy (admin-created accounts, admin resets, own password change). Change it here only. */
    public static function passwordRule(): Password
    {
        return Password::min(10)->mixedCase()->numbers();
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    /** Admin always passes. Called with no arguments it means "is this an admin?". Prefer isAdmin() for that. */
    public function hasRole(string ...$roles): bool
    {
        return $this->role === 'admin' || in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Active administrators; used to stop the system from losing its last admin. */
    public static function activeAdminCount(): int
    {
        return static::where('role', 'admin')->where('status', 'active')->count();
    }
}
