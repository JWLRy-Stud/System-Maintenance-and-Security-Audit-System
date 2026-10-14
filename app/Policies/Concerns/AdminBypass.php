<?php

namespace App\Policies\Concerns;

use App\Models\User;

/** Administrators pass every policy check; business-rule validity (valid transitions etc.) is checked elsewhere. */
trait AdminBypass
{
    public function before(User $user): ?bool
    {
        return $user->isActive() && $user->isAdmin() ? true : null;
    }
}
