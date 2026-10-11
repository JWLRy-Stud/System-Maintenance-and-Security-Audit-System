@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3"><h3>Assets</h3>
  @if(auth()->user()->hasRole())<a href="{{ route('assets.create') }}" class="btn btn-primary">New asset</a>@endif</div>
<form class="row g-2 mb-3"><div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search tag or name"></div>
  <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(\App\Models\Asset::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
  <div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div></form>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Tag</th><th>Name</th><th>Category</th><th>Location</th><th>Status</th></tr></thead><tbody>
@forelse($assets as $a)<tr><td><a href="{{ route('assets.show',$a) }}">{{ $a->asset_tag }}</a></td><td>{{ $a->name }}</td><td>{{ $a->category }}</td><td>{{ $a->location }}</td><td>{{ $a->status }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No assets found.</td></tr>@endforelse</tbody></table>
{{ $assets->links() }}
@endsection
