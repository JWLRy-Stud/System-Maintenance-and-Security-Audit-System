@extends('layouts.app')
@section('content')
@php($u = auth()->user())
<h3>{{ $req->title }} <span class="badge bg-secondary">{{ $req->status }}</span></h3>
<p class="text-muted">Asset: <a href="{{ route('assets.show',$req->asset) }}">{{ $req->asset->asset_tag }}</a> · Priority: {{ $req->priority }} · Requester: {{ $req->requester->name }} · Technician: {{ $req->technician?->name ?? '—' }}</p>
<p>{{ $req->description }}</p>
<div class="card card-body mb-3"><h6>Actions</h6>
@php($next = \App\Models\MaintenanceRequest::TRANSITIONS[$req->status])
@if($req->status==='open' && $u->role==='admin')
  <form method="POST" action="{{ route('maintenance.transition',$req) }}" class="row g-2 mb-2">@csrf<input type="hidden" name="status" value="assigned">
    <div class="col-auto"><select name="technician_id" class="form-select">@foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-primary">Assign</button></div></form>
@endif
@if($req->status==='assigned' && ($u->role==='admin' || $req->technician_id===$u->id))
  <form method="POST" action="{{ route('maintenance.transition',$req) }}">@csrf<input type="hidden" name="status" value="in_progress"><button class="btn btn-warning mb-2">Start work</button></form>
@endif
@if($req->status==='in_progress' && ($u->role==='admin' || $req->technician_id===$u->id))
  <form method="POST" action="{{ route('maintenance.transition',$req) }}">@csrf<input type="hidden" name="status" value="completed">
    <textarea name="work_summary" class="form-control mb-2" placeholder="Work summary (required)" required></textarea>
    <textarea name="completion_notes" class="form-control mb-2" placeholder="Completion notes"></textarea><button class="btn btn-success">Complete</button></form>
@endif
@if(in_array('cancelled',$next) && ($u->role==='admin' || ($req->requester_id===$u->id && $req->status==='open')))
  <form method="POST" action="{{ route('maintenance.transition',$req) }}" class="mt-2">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this request?')">Cancel request</button></form>
@endif
@if(!$next)<span class="text-muted">No further actions.</span>@endif
</div>
@if($req->records->isNotEmpty())<h5>Completion record</h5>@foreach($req->records as $r)<div class="card card-body mb-2"><strong>{{ $r->performer->name }}</strong> · {{ $r->created_at }}<br>{{ $r->work_summary }}<br><small>{{ $r->completion_notes }}</small></div>@endforeach @endif
@endsection
