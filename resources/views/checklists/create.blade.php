@extends('layouts.app')
@section('content')
<h3>New checklist template</h3>
<form method="POST" action="{{ route('checklists.store') }}" class="card card-body">@csrf
  <div class="mb-3"><label class="form-label">Name</label><input name="name" value="{{ old('name') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Description</label><input name="description" value="{{ old('description') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Questions (one per line)</label><textarea name="questions" rows="10" class="form-control" placeholder="Is the firewall enabled?&#10;Are OS updates applied?">{{ old('questions') }}</textarea></div>
  <button class="btn btn-primary">Create template</button>
</form>
@endsection
