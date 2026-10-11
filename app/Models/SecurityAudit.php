<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAudit extends Model
{
    protected $fillable = ['title', 'scope', 'auditor_id', 'status', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function auditor() { return $this->belongsTo(User::class, 'auditor_id'); }

    public function findings() { return $this->hasMany(AuditFinding::class); }
}
