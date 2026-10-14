<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'technician', 'auditor', 'staff'];

    // BUG B-13 [OPEN]: 'role' and 'status' are mass-assignable. A future $user->update($request->all()) would be
    //   privilege escalation. FIX: remove them from $fillable and set them with forceFill() in UserAdminService,
    //   UserController::store and DatabaseSeeder; add a test that mass-assigning 'role' is ignored.
    protected $fillable = ['name', 'email', 'password', 'role', 'status'];

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
