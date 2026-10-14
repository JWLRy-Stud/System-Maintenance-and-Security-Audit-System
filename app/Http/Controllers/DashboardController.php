<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AuditFinding;
use App\Models\MaintenanceRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canMaintenance = $user->hasRole('technician', 'staff');
        $open = ['open', 'assigned', 'in_progress'];

        // Counts only cover what this user may open, so every number matches the page it links to.
        $maintenance = fn () => MaintenanceRequest::visibleTo($user)->whereIn('status', $open);

        return view('dashboard', [
            'assets' => Asset::count(),
            'canMaintenance' => $canMaintenance,
            'pending' => $canMaintenance ? $maintenance()->count() : 0,
            'overdue' => $canMaintenance ? $maintenance()->where('scheduled_at', '<', now())->count() : 0,
            'findings' => AuditFinding::whereIn('status', ['open', 'in_progress'])
                ->when(! $user->hasRole('auditor'), fn ($q) => $q->where('assigned_to', $user->id))->count(),
            'recent' => $user->isAdmin() ? ActivityLog::with('actor')->latest('id')->limit(8)->get() : collect(),
        ]);
    }
}
