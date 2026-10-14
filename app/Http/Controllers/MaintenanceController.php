<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceStoreRequest;
use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $items = MaintenanceRequest::with('asset', 'requester', 'technician')->visibleTo($request->user())
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(15)->withQueryString();

        return view('maintenance.index', compact('items'));
    }

    public function create()
    {
        return view('maintenance.create', ['assets' => Asset::where('status', '!=', 'retired')->orderBy('asset_tag')->get()]);
    }

    public function store(MaintenanceStoreRequest $request)
    {
        $req = MaintenanceRequest::create($request->validated() + ['requester_id' => $request->user()->id, 'status' => 'open']);
        ActivityLogService::record('maintenance.created', $req);

        return redirect()->route('maintenance.show', $req)->with('ok', 'Request submitted.');
    }

    public function show(Request $request, MaintenanceRequest $maintenance)
    {
        Gate::authorize('view', $maintenance);
        $maintenance->load('asset', 'requester', 'technician', 'records.performer');

        return view('maintenance.show', [
            'req' => $maintenance,
            'technicians' => User::where('role', 'technician')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function transition(Request $request, MaintenanceRequest $maintenance, MaintenanceService $service)
    {
        Gate::authorize('view', $maintenance);

        $data = $request->validate([
            'status' => ['required', 'in:assigned,in_progress,completed,cancelled'],
            'technician_id' => ['required_if:status,assigned', 'nullable', 'exists:users,id,role,technician,status,active'],
            'work_summary' => ['required_if:status,completed', 'nullable', 'string', 'max:5000'],
            'completion_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $service->transition($maintenance, $data['status'], $request->user(), $data);

        return back()->with('ok', 'Status updated.');
    }
}
