@extends('layouts.app')
@section('title', 'New checklist template')
@section('width', 'narrow')
@section('content')
<a class="back" href="{{ route('checklists.index') }}"><x-icon name="back" :size="16" />Checklists</a>
<x-page-header title="New checklist template" sub="Each question becomes a pass, fail or N/A check. A fail raises a finding." />

<form method="POST" action="{{ route('checklists.store') }}" class="surface form-card form-stack">@csrf
  <div>
    <label class="form-label" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Workstation baseline">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="description">Description <span class="muted fw-normal">(optional)</span></label>
    <input id="description" name="description" value="{{ old('description') }}" class="form-control @error('description') is-invalid @enderror">
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="questions">Questions</label>
    <textarea id="questions" name="questions" rows="10" class="form-control @error('questions') is-invalid @enderror" placeholder="Is the firewall enabled?&#10;Are OS updates applied?">{{ old('questions') }}</textarea>
    @error('questions')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-hint">One question per line.</div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Create template</button>
    <a class="btn btn-quiet" href="{{ route('checklists.index') }}">Cancel</a>
  </div>
</form>
@endsection
