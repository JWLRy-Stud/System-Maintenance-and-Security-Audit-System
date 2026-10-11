<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /** Allowed workflow transitions. Anything else is rejected by MaintenanceService. */
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

    public function asset() { return $this->belongsTo(Asset::class); }

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }

    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }

    public function records() { return $this->hasMany(MaintenanceRecord::class); }
}
