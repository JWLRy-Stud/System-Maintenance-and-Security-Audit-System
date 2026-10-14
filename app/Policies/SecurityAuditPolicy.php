<?php

namespace App\Policies;

use App\Models\SecurityAudit;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

class SecurityAuditPolicy
{
    use AdminBypass;

    /** Auditors may read every audit (peer visibility); other roles reach findings only through "My tasks". */
    public function view(User $user, SecurityAudit $audit): bool
    {
        return $user->isActive() && $user->role === 'auditor';
    }

    /** Change an audit: answer checks, record findings, complete it. Owner or admin only. */
    public function manage(User $user, SecurityAudit $audit): bool
    {
        return $user->isActive() && $user->role === 'auditor' && $audit->auditor_id === $user->id;
    }
}
