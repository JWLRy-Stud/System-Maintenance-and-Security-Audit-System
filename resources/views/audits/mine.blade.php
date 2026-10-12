@extends('layouts.app')
@section('content')
<h3>My remediation tasks</h3>
<table class="table table-sm bg-white"><thead><tr><th>Finding</th><th>Audit</th><th>Severity</th><th>Status</th><th>Due</th><th></th></tr></thead><tbody>
@forelse($findings as $f)<tr><td>{{ $f->title }}<br><small class="text-muted">{{ $f->description }}</small></td><td>{{ $f->audit->title }}</td><td>{{ $f->severity }}</td><td>{{ $f->status }}</td><td>{{ $f->due_date?->toDateString() }}</td>
<td>@foreach(\App\Models\AuditFinding::TRANSITIONS[$f->status] as $to)<form method="POST" action="{{ route('findings.update',$f) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $to }}"><button class="btn btn-sm btn-outline-primary">{{ $to }}</button></form> @endforeach</td></tr>
@empty<tr><td colspan="6" class="text-muted">Nothing assigned to you.</td></tr>@endforelse</tbody></table>
{{ $findings->links() }}
@endsection
