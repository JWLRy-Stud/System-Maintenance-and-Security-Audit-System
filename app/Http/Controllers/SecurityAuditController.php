<?php

namespace App\Http\Controllers;

use App\Models\AuditFinding;
use App\Models\SecurityAudit;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SecurityAuditController extends Controller
{
    private function authorizeOwner(Request $r, SecurityAudit $audit): void
    {
        abort_unless($r->user()->role === 'admin' || $audit->auditor_id === $r->user()->id, 403);
    }

    public function index() { return view('audits.index', ['audits' => SecurityAudit::with('auditor')->withCount('findings')->latest()->paginate(15)]); }

    public function create() { return view('audits.create'); }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'scope' => 'nullable|string|max:5000']);
        $audit = SecurityAudit::create($data + ['auditor_id' => $request->user()->id, 'status' => 'in_progress', 'started_at' => now()]);
        ActivityLogService::record('audit.created', $audit);

        return redirect()->route('audits.show', $audit)->with('ok', 'Audit started.');
    }

    public function show(SecurityAudit $audit)
    {
        $audit->load('auditor', 'findings.assignee');

        return view('audits.show', ['audit' => $audit, 'users' => User::where('status', 'active')->orderBy('name')->get()]);
    }

    public function complete(Request $request, SecurityAudit $audit)
    {
        $this->authorizeOwner($request, $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['status' => 'Audit is already completed.']);
        }
        $audit->update(['status' => 'completed', 'completed_at' => now()]);
        ActivityLogService::record('audit.completed', $audit);

        return back()->with('ok', 'Audit completed.');
    }

    public function storeFinding(Request $request, SecurityAudit $audit)
    {
        $this->authorizeOwner($request, $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['title' => 'Cannot add findings to a completed audit.']);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'severity' => 'required|in:'.implode(',', AuditFinding::SEVERITIES),
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);
        $finding = $audit->findings()->create($data + ['status' => 'open']);
        ActivityLogService::record('finding.created', $finding);

        return back()->with('ok', 'Finding recorded.');
    }

    public function updateFinding(Request $request, AuditFinding $finding)
    {
        $user = $request->user();
        abort_unless($user->role === 'admin' || $finding->audit->auditor_id === $user->id || $finding->assigned_to === $user->id, 403);
        $to = $request->validate(['status' => 'required|in:open,in_progress,resolved,accepted_risk'])['status'];

        if (! in_array($to, AuditFinding::TRANSITIONS[$finding->status] ?? [], true)) {
            ActivityLogService::record("finding.{$to}", $finding, 'denied');
            throw ValidationException::withMessages(['status' => "Cannot move a {$finding->status} finding to {$to}."]);
        }

        DB::transaction(function () use ($finding, $to) {
            $finding->update(['status' => $to, 'resolved_at' => $to === 'resolved' ? now() : null]);
            ActivityLogService::record("finding.{$to}", $finding);
        });

        return back()->with('ok', 'Finding updated.');
    }
}
