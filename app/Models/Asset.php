<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    public const STATUSES = ['active', 'under_maintenance', 'retired'];

    protected $fillable = ['asset_tag', 'name', 'category', 'location', 'status', 'assigned_user_id'];

    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_user_id'); }

    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class); }
}
