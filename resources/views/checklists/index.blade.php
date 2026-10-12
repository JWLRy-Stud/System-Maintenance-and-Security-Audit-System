@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3"><h3>Audit checklist templates</h3><a class="btn btn-primary" href="{{ route('checklists.create') }}">New template</a></div>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Name</th><th>Description</th><th>Items</th></tr></thead><tbody>
@forelse($templates as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->description }}</td><td>{{ $t->items_count }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No templates yet.</td></tr>@endforelse</tbody></table>
@endsection
