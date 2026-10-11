@extends('layouts.app')
@section('content')
<h3>Activity log</h3>
<form class="row g-2 mb-3"><div class="col-md-4"><input name="action" value="{{ request('action') }}" class="form-control" placeholder="Action prefix, e.g. maintenance"></div>
<div class="col-md-3"><select name="outcome" class="form-select"><option value="">Any outcome</option><option @selected(request('outcome')==='success')>success</option><option @selected(request('outcome')==='denied')>denied</option><option @selected(request('outcome')==='failed')>failed</option></select></div>
<div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div></form>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Outcome</th><th>Request ID</th></tr></thead><tbody>
@foreach($logs as $l)<tr><td>{{ $l->created_at }}</td><td>{{ $l->actor?->name ?? '—' }}</td><td>{{ $l->action }}</td><td>{{ $l->subject_type }} #{{ $l->subject_id }}</td><td>{{ $l->outcome }}</td><td class="small">{{ $l->request_id }}</td></tr>@endforeach</tbody></table>
{{ $logs->links() }}
@endsection
