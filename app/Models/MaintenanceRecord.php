<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['maintenance_request_id', 'performed_by', 'work_summary', 'completion_notes'];

    public function performer() { return $this->belongsTo(User::class, 'performed_by'); }
}
