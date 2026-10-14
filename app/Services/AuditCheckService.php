<?php

namespace App\Services;

use App\Models\AuditCheck;
use App\Models\AuditFinding;
use Illuminate\Support\Facades\DB;

/**
 * Answering a checklist item keeps its linked finding in step:
 *  - fail            -> raise a finding once; later saves keep its severity/notes current while it is still being worked
 *  - fail -> pass/NA -> the finding was raised by that failure, so close it (resolved) unless it is already closed
 *  - back to fail    -> reopen a finding that had been resolved (accepted risk is a deliberate decision and is left alone)
 */
class AuditCheckService
{
    private const ACTIVE = ['open', 'in_progress'];

    public function answer(AuditCheck $check, array $data): AuditCheck
    {
        return DB::transaction(function () use ($check, $data) {
            $audit = $check->audit;
            $check->update(['result' => $data['result'], 'notes' => $data['notes'] ?? null]);
            $finding = $check->finding_id ? AuditFinding::find($check->finding_id) : null;

            if ($data['result'] === 'fail') {
                if (! $finding) {
                    $finding = $audit->findings()->create([
                        'title' => mb_substr($check->question, 0, 255), 'description' => $data['notes'] ?? null,
                        'severity' => $data['severity'], 'status' => 'open',
                    ]);
                    $check->update(['finding_id' => $finding->id]);
                    ActivityLogService::record('finding.created', $finding);
                } elseif ($finding->status === 'resolved') {
                    $finding->update(['status' => 'open', 'resolved_at' => null, 'severity' => $data['severity'], 'description' => $data['notes'] ?? $finding->description]);
                    ActivityLogService::record('finding.reopened', $finding);
                } elseif (in_array($finding->status, self::ACTIVE, true)) {
                    $finding->update(['severity' => $data['severity'], 'description' => $data['notes'] ?? $finding->description]);
                }
            } elseif ($finding && in_array($finding->status, self::ACTIVE, true)) {
                $finding->update(['status' => 'resolved', 'resolved_at' => now()]);
                ActivityLogService::record('finding.auto_resolved', $finding);
            }

            ActivityLogService::record('check.answered', $check);

            return $check;
        });
    }
}
