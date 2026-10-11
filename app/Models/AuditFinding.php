<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditFinding extends Model
{
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public const TRANSITIONS = [
        'open' => ['in_progress', 'accepted_risk'],
        'in_progress' => ['resolved', 'open'],
        'resolved' => [],
        'accepted_risk' => [],
    ];

    protected $fillable = ['security_audit_id', 'title', 'description', 'severity', 'status', 'assigned_to', 'due_date', 'resolved_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'resolved_at' => 'datetime'];
    }

    public function audit() { return $this->belongsTo(SecurityAudit::class, 'security_audit_id'); }

    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}
