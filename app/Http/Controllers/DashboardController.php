<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditFinding;
use App\Models\MaintenanceRequest;
use App\Models\ActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('dashboard', [
            'assets' => Asset::count(),
            'pending' => MaintenanceRequest::whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'overdue' => MaintenanceRequest::whereIn('status', ['open', 'assigned', 'in_progress'])->where('scheduled_at', '<', now())->count(),
            'findings' => AuditFinding::whereIn('status', ['open', 'in_progress'])->count(),
            'recent' => $user->hasRole('admin') ? ActivityLog::with('actor')->latest('id')->limit(8)->get() : collect(),
        ]);
    }
}
