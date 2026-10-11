<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceService
{
    public function transition(MaintenanceRequest $req, string $to, User $actor, array $data = []): MaintenanceRequest
    {
        if (! in_array($to, MaintenanceRequest::TRANSITIONS[$req->status] ?? [], true)) {
            ActivityLogService::record("maintenance.{$to}", $req, 'denied');
            throw ValidationException::withMessages(['status' => "Cannot move a {$req->status} request to {$to}."]);
        }

        $isAssignedTech = $actor->role === 'technician' && $req->technician_id === $actor->id;
        $allowed = match ($to) {
            'assigned' => $actor->role === 'admin',
            'in_progress', 'completed' => $actor->role === 'admin' || $isAssignedTech,
            'cancelled' => $actor->role === 'admin' || ($req->requester_id === $actor->id && $req->status === 'open'),
        };
        if (! $allowed) {
            ActivityLogService::record("maintenance.{$to}", $req, 'denied');
            abort(403);
        }

        return DB::transaction(function () use ($req, $to, $actor, $data) {
            $payload = ['status' => $to];

            if ($to === 'assigned') {
                $payload['technician_id'] = $data['technician_id'];
            }
            if ($to === 'in_progress') {
                $req->asset->update(['status' => 'under_maintenance']);
            }
            if ($to === 'completed') {
                $payload['completed_at'] = now();
                $req->records()->create([
                    'performed_by' => $actor->id,
                    'work_summary' => $data['work_summary'],
                    'completion_notes' => $data['completion_notes'] ?? null,
                ]);
                $req->asset->update(['status' => 'active']);
            }

            $req->update($payload);
            ActivityLogService::record("maintenance.{$to}", $req);

            return $req;
        });
    }
}
