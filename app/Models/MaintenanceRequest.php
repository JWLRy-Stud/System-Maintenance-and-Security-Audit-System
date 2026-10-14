<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /** Allowed workflow transitions. Anything else is rejected by MaintenanceService. */
    // BUG B-18 [OPEN]: 'assigned' => has no 'assigned', so a request cannot be reassigned. If its technician is deactivated
    //   the request is stuck (only an admin can still start/complete it). FIX: allow assigned -> assigned (admin only), show the assign form on
    //   assigned requests too, and add a test.
    public const TRANSITIONS = [
        'open' => ['assigned', 'cancelled'],
        'assigned' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = ['asset_id', 'requester_id', 'technician_id', 'title', 'description', 'priority', 'status', 'scheduled_at', 'completed_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /**
     * Record-level visibility, the single source of truth (MaintenanceRequestPolicy::view mirrors it).
     * admin: everything; technician: requests assigned to them or raised by them; staff: their own; others: nothing.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query,
            'technician' => $query->where(fn ($w) => $w->where('technician_id', $user->id)->orWhere('requester_id', $user->id)),
            'staff' => $query->where('requester_id', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function asset() { return $this->belongsTo(Asset::class); }

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }

    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }

    public function records() { return $this->hasMany(MaintenanceRecord::class); }
}
