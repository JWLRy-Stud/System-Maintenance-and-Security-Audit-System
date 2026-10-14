<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Managing accounts (create, edit, role change, activate/deactivate, password reset) is admin-only. */
class UserPolicy
{
    use AdminBypass;

    public function manage(User $user, User $target): bool
    {
        return false; // only admins, granted by before()
    }
}
