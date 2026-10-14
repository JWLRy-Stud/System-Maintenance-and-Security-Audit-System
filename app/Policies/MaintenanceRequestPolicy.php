<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

class MaintenanceRequestPolicy
{
    use AdminBypass;

    /** Mirrors MaintenanceRequest::scopeVisibleTo(). */
    public function view(User $user, MaintenanceRequest $req): bool
    {
        return $user->isActive() && match ($user->role) {
            'technician' => $req->technician_id === $user->id || $req->requester_id === $user->id,
            'staff' => $req->requester_id === $user->id,
            default => false,
        };
    }

    /**
     * May $user move $req to $to? (Whether the move is a legal workflow step is MaintenanceService's job.)
     * assign: admin only · start/complete: admin or the assigned technician · cancel: admin or the requester while still open.
     */
    public function transition(User $user, MaintenanceRequest $req, string $to): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return match ($to) {
            'in_progress', 'completed' => $user->role === 'technician' && $req->technician_id === $user->id,
            'cancelled' => $req->requester_id === $user->id && $req->status === 'open',
            default => false, // 'assigned' and anything unknown: admin only (granted by before())
        };
    }
}
