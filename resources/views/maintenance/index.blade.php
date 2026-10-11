@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3"><h3>Maintenance requests</h3><a href="{{ route('maintenance.create') }}" class="btn btn-primary">New request</a></div>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Title</th><th>Asset</th><th>Priority</th><th>Status</th><th>Technician</th></tr></thead><tbody>
@forelse($items as $m)<tr><td><a href="{{ route('maintenance.show',$m) }}">{{ $m->title }}</a></td><td>{{ $m->asset->asset_tag }}</td><td>{{ $m->priority }}</td><td>{{ $m->status }}</td><td>{{ $m->technician?->name ?? '—' }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">No requests.</td></tr>@endforelse</tbody></table>
{{ $items->links() }}
@endsection
