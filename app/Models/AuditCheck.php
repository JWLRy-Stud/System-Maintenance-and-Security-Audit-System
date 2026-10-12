<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditCheck extends Model
{
    public const RESULTS = ['pass', 'fail', 'not_applicable'];

    protected $fillable = ['security_audit_id', 'question', 'position', 'result', 'notes', 'finding_id'];

    public function audit() { return $this->belongsTo(SecurityAudit::class, 'security_audit_id'); }

    public function finding() { return $this->belongsTo(AuditFinding::class); }
}
