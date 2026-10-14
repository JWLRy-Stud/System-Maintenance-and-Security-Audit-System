<?php

namespace App\Policies;

use App\Models\AuditFinding;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

class AuditFindingPolicy
{
    use AdminBypass;

    private function isOwner(User $user, AuditFinding $finding): bool
    {
        return $user->role === 'auditor' && $finding->audit->auditor_id === $user->id;
    }

    private function isAssignee(User $user, AuditFinding $finding): bool
    {
        return $finding->assigned_to !== null && $finding->assigned_to === $user->id;
    }

    /** Anyone connected to the finding may touch it at all (further limited per action below). */
    public function update(User $user, AuditFinding $finding): bool
    {
        return $user->isActive() && ($this->isOwner($user, $finding) || $this->isAssignee($user, $finding));
    }

    /**
     * Status changes. The audit owner and admin may make any legal move.
     * The remediation owner (assignee, any role) may only start work and mark it resolved:
     * accepting a risk or reopening a finding is a judgement call that belongs to the auditor.
     */
    public function transition(User $user, AuditFinding $finding, string $to): bool
    {
        if (! $user->isActive()) {
            return false;
        }
        if ($this->isOwner($user, $finding)) {
            return true;
        }

        return $this->isAssignee($user, $finding) && in_array($to, ['in_progress', 'resolved'], true);
    }

    /** Choose or change the remediation owner and due date: audit owner or admin. */
    public function assign(User $user, AuditFinding $finding): bool
    {
        return $user->isActive() && $this->isOwner($user, $finding);
    }
}
