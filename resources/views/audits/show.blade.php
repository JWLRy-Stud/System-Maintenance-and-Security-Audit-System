@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between"><h3>{{ $audit->title }} <span class="badge bg-secondary">{{ $audit->status }}</span></h3>
@if($audit->status!=='completed')<form method="POST" action="{{ route('audits.complete',$audit) }}">@csrf<button class="btn btn-success">Complete audit</button></form>@endif</div>
<p class="text-muted">Auditor: {{ $audit->auditor->name }} · {{ $audit->scope }}</p>
@if($audit->checks->isNotEmpty())
<h5>Checklist ({{ $audit->checks->where('result','!=','pending')->count() }}/{{ $audit->checks->count() }} answered)</h5>
<table class="table table-sm bg-white mb-4"><tbody>
@foreach($audit->checks as $c)<tr><td style="width:40%">{{ $c->question }}</td>
<td>@if($audit->status==='completed')<strong>{{ $c->result }}</strong> <small>{{ $c->notes }}</small>
@else<form method="POST" action="{{ route('checks.update',$c) }}" class="row g-1">@csrf @method('PATCH')
  <div class="col-auto"><select name="result" class="form-select form-select-sm">@foreach(\App\Models\AuditCheck::RESULTS as $r)<option value="{{ $r }}" @selected($c->result===$r)>{{ $r }}</option>@endforeach</select></div>
  <div class="col-auto"><select name="severity" class="form-select form-select-sm" title="Severity (used when result is fail)">@foreach(\App\Models\AuditFinding::SEVERITIES as $sv)<option>{{ $sv }}</option>@endforeach</select></div>
  <div class="col"><input name="notes" value="{{ $c->notes }}" class="form-control form-control-sm" placeholder="Notes / evidence reference"></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Save</button></div>
</form>@endif
@if($c->finding_id)<span class="badge bg-danger">finding raised</span>@endif</td></tr>@endforeach
</tbody></table>
@endif
<h5>Findings</h5>
<table class="table table-sm bg-white"><thead><tr><th>Title</th><th>Severity</th><th>Status</th><th>Assigned</th><th>Due</th><th></th></tr></thead><tbody>
@forelse($audit->findings as $f)<tr><td>{{ $f->title }}<br><small class="text-muted">{{ $f->description }}</small></td><td>{{ $f->severity }}</td><td>{{ $f->status }}</td><td>{{ $f->assignee?->name }}</td><td>{{ $f->due_date?->toDateString() }}</td>
<td>@foreach(\App\Models\AuditFinding::TRANSITIONS[$f->status] as $to)<form method="POST" action="{{ route('findings.update',$f) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $to }}"><button class="btn btn-sm btn-outline-primary">{{ $to }}</button></form> @endforeach</td></tr>
@empty<tr><td colspan="6" class="text-muted">No findings yet.</td></tr>@endforelse</tbody></table>
@if($audit->status!=='completed')
<div class="card card-body"><h6>Record finding</h6>
<form method="POST" action="{{ route('findings.store',$audit) }}">@csrf
  <input name="title" class="form-control mb-2" placeholder="Title" value="{{ old('title') }}">
  <textarea name="description" class="form-control mb-2" placeholder="Description / evidence reference"></textarea>
  <div class="row g-2 mb-2"><div class="col-md-3"><select name="severity" class="form-select">@foreach(\App\Models\AuditFinding::SEVERITIES as $s)<option>{{ $s }}</option>@endforeach</select></div>
  <div class="col-md-5"><select name="assigned_to" class="form-select"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
  <div class="col-md-4"><input type="date" name="due_date" class="form-control"></div></div>
  <button class="btn btn-primary">Add finding</button></form></div>
@endif
@endsection
