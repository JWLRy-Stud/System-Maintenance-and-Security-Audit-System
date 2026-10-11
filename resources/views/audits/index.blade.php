@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3"><h3>Security audits</h3><div><a class="btn btn-outline-secondary" href="{{ route('reports.findings') }}">Export findings CSV</a> <a class="btn btn-primary" href="{{ route('audits.create') }}">New audit</a></div></div>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Title</th><th>Auditor</th><th>Status</th><th>Findings</th></tr></thead><tbody>
@forelse($audits as $a)<tr><td><a href="{{ route('audits.show',$a) }}">{{ $a->title }}</a></td><td>{{ $a->auditor->name }}</td><td>{{ $a->status }}</td><td>{{ $a->findings_count }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No audits.</td></tr>@endforelse</tbody></table>
{{ $audits->links() }}
@endsection
