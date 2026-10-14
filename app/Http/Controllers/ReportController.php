<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditFinding;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function logs(Request $request)
    {
        $logs = ActivityLog::with('actor')
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', "{$a}%"))
            ->when($request->outcome, fn ($q, $o) => $q->where('outcome', $o))
            ->latest('id')->paginate(25)->withQueryString();

        return view('reports.logs', compact('logs'));
    }

    public function findingsCsv()
    {
        // Neutralise spreadsheet formula injection (=, +, -, @, TAB and CR prefixes).
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Audit', 'Finding', 'Severity', 'Status', 'Assigned To', 'Due']);
            AuditFinding::with('audit', 'assignee')->orderBy('id')->chunk(200, function ($rows) use ($out, $safe) {
                foreach ($rows as $f) {
                    fputcsv($out, array_map($safe, [$f->audit->title, $f->title, $f->severity, $f->status, $f->assignee?->name, $f->due_date?->toDateString()]));
                }
            });
            fclose($out);
        }, 'findings.csv', ['Content-Type' => 'text/csv']);
    }
}
