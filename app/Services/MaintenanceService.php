<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MaintenanceService
{
    public function transition(MaintenanceRequest $req, string $to, User $actor, array $data = []): MaintenanceRequest
    {
        if (! in_array($to, MaintenanceRequest::TRANSITIONS[$req->status] ?? [], true)) {
            ActivityLogService::record("maintenance.{$to}", $req, 'denied');
            throw ValidationException::withMessages(['status' => "Cannot move a {$req->status} request to {$to}."]);
        }

        // Who may do this is decided in one place: MaintenanceRequestPolicy::transition().
        if (Gate::forUser($actor)->denies('transition', [$req, $to])) {
            ActivityLogService::record("maintenance.{$to}", $req, 'denied');
            abort(403);
        }

        // BUG B-19 [OPEN]: no row lock. Two simultaneous requests can both pass the checks above (e.g. complete the same
        //   request twice and create two completion records). FIX: inside the transaction reload the row with
        //   lockForUpdate() and re-check its status. Same pattern applies to SecurityAuditController::updateFinding.
        return DB::transaction(function () use ($req, $to, $actor, $data) {
            $payload = ['status' => $to];

            if ($to === 'assigned') {
                $payload['technician_id'] = $data['technician_id'];
            }
            if ($to === 'in_progress' && $req->asset->status !== 'retired') {
                $req->asset->update(['status' => 'under_maintenance']);
            }
            if ($to === 'completed') {
                $payload['completed_at'] = now();
                $req->records()->create([
                    'performed_by' => $actor->id,
                    'work_summary' => $data['work_summary'],
                    'completion_notes' => $data['completion_notes'] ?? null,
                ]);
            }

            $req->update($payload);

            if ($to === 'completed') {
                $this->releaseAsset($req);
            }
            ActivityLogService::record("maintenance.{$to}", $req);

            return $req;
        });
    }

    /**
     * Put the asset back in service only if it is still marked under maintenance (never un-retire it)
     * and no other request on the same asset is still being worked on.
     */
    private function releaseAsset(MaintenanceRequest $done): void
    {
        $asset = $done->asset;
        $stillBusy = MaintenanceRequest::where('asset_id', $asset->id)
            ->where('status', 'in_progress')->whereKeyNot($done->id)->exists();

        if ($asset->status === 'under_maintenance' && ! $stillBusy) {
            $asset->update(['status' => 'active']);
        }
    }
}
