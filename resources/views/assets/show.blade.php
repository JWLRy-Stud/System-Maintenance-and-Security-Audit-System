@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between"><h3>{{ $asset->asset_tag }} — {{ $asset->name }}</h3>
  @if(auth()->user()->hasRole())<a class="btn btn-outline-primary" href="{{ route('assets.edit',$asset) }}">Edit</a>@endif</div>
<p class="text-muted">{{ $asset->category }} · {{ $asset->location }} · {{ $asset->status }} · Assigned: {{ $asset->assignedUser?->name ?? '—' }}</p>
@if(auth()->user()->hasRole('technician'))
<h5>Maintenance history</h5>
<ul class="list-group">@forelse($asset->maintenanceRequests as $m)<li class="list-group-item"><a href="{{ route('maintenance.show',$m) }}">{{ $m->title }}</a> — {{ $m->status }}</li>@empty<li class="list-group-item text-muted">None.</li>@endforelse</ul>
@endif
@endsection
