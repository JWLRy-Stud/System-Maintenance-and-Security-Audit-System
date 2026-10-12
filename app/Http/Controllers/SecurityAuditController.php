<?php

namespace App\Http\Controllers;

use App\Models\AuditCheck;
use App\Models\AuditFinding;
use App\Models\ChecklistTemplate;
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

    /** Findings assigned to the current user (remediation owner view, any role). */
    public function mine(Request $request)
    {
        $findings = AuditFinding::with('audit')->where('assigned_to', $request->user()->id)->latest()->paginate(15);

        return view('audits.mine', compact('findings'));
    }

    public function create() { return view('audits.create', ['templates' => ChecklistTemplate::orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'scope' => 'nullable|string|max:5000',
            'checklist_template_id' => 'nullable|exists:checklist_templates,id',
        ]);

        $audit = DB::transaction(function () use ($data, $request) {
            $audit = SecurityAudit::create([
                'title' => $data['title'], 'scope' => $data['scope'] ?? null,
                'auditor_id' => $request->user()->id, 'status' => 'in_progress', 'started_at' => now(),
            ]);
            if (! empty($data['checklist_template_id'])) {
                foreach (ChecklistTemplate::with('items')->find($data['checklist_template_id'])->items as $i => $item) {
                    $audit->checks()->create(['question' => $item->question, 'position' => $i]);
                }
            }
            ActivityLogService::record('audit.created', $audit);

            return $audit;
        });

        return redirect()->route('audits.show', $audit)->with('ok', 'Audit started.');
    }

    public function show(SecurityAudit $audit)
    {
        $audit->load('auditor', 'findings.assignee', 'checks');

        return view('audits.show', ['audit' => $audit, 'users' => User::where('status', 'active')->orderBy('name')->get()]);
    }

    public function complete(Request $request, SecurityAudit $audit)
    {
        $this->authorizeOwner($request, $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['status' => 'Audit is already completed.']);
        }
        if ($audit->checks()->where('result', 'pending')->exists()) {
            ActivityLogService::record('audit.completed', $audit, 'denied');
            throw ValidationException::withMessages(['status' => 'Answer every checklist item before completing the audit.']);
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

    /** Answer a checklist item. A "fail" automatically raises a linked finding (once). */
    public function updateCheck(Request $request, AuditCheck $check)
    {
        $audit = $check->audit;
        $this->authorizeOwner($request, $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['result' => 'Audit is completed; checklist is locked.']);
        }
        $data = $request->validate([
            'result' => 'required|in:'.implode(',', AuditCheck::RESULTS),
            'notes' => 'nullable|string|max:5000',
            'severity' => 'required_if:result,fail|nullable|in:'.implode(',', AuditFinding::SEVERITIES),
        ]);

        DB::transaction(function () use ($check, $audit, $data) {
            $check->update(['result' => $data['result'], 'notes' => $data['notes'] ?? null]);
            if ($data['result'] === 'fail' && ! $check->finding_id) {
                $finding = $audit->findings()->create([
                    'title' => mb_substr($check->question, 0, 255), 'description' => $data['notes'] ?? null,
                    'severity' => $data['severity'], 'status' => 'open',
                ]);
                $check->update(['finding_id' => $finding->id]);
                ActivityLogService::record('finding.created', $finding);
            }
            ActivityLogService::record('check.answered', $check);
        });

        return back()->with('ok', 'Checklist item saved.');
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
