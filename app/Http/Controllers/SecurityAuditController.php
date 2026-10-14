<?php

namespace App\Http\Controllers;

use App\Models\AuditCheck;
use App\Models\AuditFinding;
use App\Models\ChecklistTemplate;
use App\Models\SecurityAudit;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AuditCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SecurityAuditController extends Controller
{
    /** Only active people can be given remediation work. */
    private function activeUser(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('users', 'id')->where('status', 'active');
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
        Gate::authorize('view', $audit);
        $audit->load('auditor', 'findings.assignee', 'checks');

        return view('audits.show', ['audit' => $audit, 'users' => User::where('status', 'active')->orderBy('name')->get()]);
    }

    public function complete(Request $request, SecurityAudit $audit)
    {
        Gate::authorize('manage', $audit);
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
        Gate::authorize('manage', $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['title' => 'Cannot add findings to a completed audit.']);
        }
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'severity' => 'required|in:'.implode(',', AuditFinding::SEVERITIES),
            'assigned_to' => ['nullable', $this->activeUser()],
            'due_date' => 'nullable|date',
        ]);
        $finding = $audit->findings()->create($data + ['status' => 'open']);
        ActivityLogService::record('finding.created', $finding);

        return back()->with('ok', 'Finding recorded.');
    }

    /** Answer a checklist item. A "fail" raises a linked finding; changing it back closes that finding (see AuditCheckService). */
    public function updateCheck(Request $request, AuditCheck $check, AuditCheckService $service)
    {
        $audit = $check->audit;
        Gate::authorize('manage', $audit);
        if ($audit->status === 'completed') {
            throw ValidationException::withMessages(['result' => 'Audit is completed; checklist is locked.']);
        }
        $data = $request->validate([
            'result' => 'required|in:'.implode(',', AuditCheck::RESULTS),
            'notes' => 'nullable|string|max:5000',
            'severity' => 'required_if:result,fail|nullable|in:'.implode(',', AuditFinding::SEVERITIES),
        ]);

        $service->answer($check, $data);

        return back()->with('ok', 'Checklist item saved.');
    }

    public function updateFinding(Request $request, AuditFinding $finding)
    {
        Gate::authorize('update', $finding); // must be the audit owner, the assignee or an admin
        $to = $request->validate(['status' => 'required|in:open,in_progress,resolved,accepted_risk'])['status'];

        if (! in_array($to, AuditFinding::TRANSITIONS[$finding->status] ?? [], true)) {
            ActivityLogService::record("finding.{$to}", $finding, 'denied');
            throw ValidationException::withMessages(['status' => "Cannot move a {$finding->status} finding to {$to}."]);
        }
        if (Gate::denies('transition', [$finding, $to])) {
            ActivityLogService::record("finding.{$to}", $finding, 'denied');
            abort(403, 'Only the auditor can do that.');
        }

        DB::transaction(function () use ($finding, $to) {
            $finding->update(['status' => $to, 'resolved_at' => $to === 'resolved' ? now() : null]);
            ActivityLogService::record("finding.{$to}", $finding);
        });

        return back()->with('ok', 'Finding updated.');
    }

    /** Choose or change who fixes a finding and by when (also needed for findings raised automatically by failed checks). */
    public function assignFinding(Request $request, AuditFinding $finding)
    {
        Gate::authorize('assign', $finding);
        if (! in_array($finding->status, ['open', 'in_progress'], true)) {
            throw ValidationException::withMessages(['assigned_to' => 'A closed finding cannot be reassigned.']);
        }
        $data = $request->validate([
            'assigned_to' => ['nullable', $this->activeUser()],
            'due_date' => 'nullable|date',
        ]);

        $finding->update(['assigned_to' => $data['assigned_to'] ?? null, 'due_date' => $data['due_date'] ?? null]);
        ActivityLogService::record('finding.assigned', $finding);

        return back()->with('ok', 'Finding assignment saved.');
    }
}
