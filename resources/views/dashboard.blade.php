@extends('layouts.app')
@section('content')
<h3 class="mb-3">Dashboard</h3>
<div class="row g-3 mb-4">
  @foreach([['Assets',$assets],['Pending maintenance',$pending],['Overdue tasks',$overdue],['Open findings',$findings]] as [$l,$v])
  <div class="col-6 col-md-3"><div class="card text-center"><div class="card-body"><div class="fs-2 fw-bold">{{ $v }}</div><div class="text-muted">{{ $l }}</div></div></div></div>
  @endforeach
</div>
@if($recent->isNotEmpty())
<div class="card"><div class="card-header">Recent activity</div><ul class="list-group list-group-flush">
@foreach($recent as $l)<li class="list-group-item small">{{ $l->created_at }} · {{ $l->actor?->name ?? 'system' }} · {{ $l->action }} · {{ $l->outcome }}</li>@endforeach
</ul></div>
@endif
@endsection
