@extends('layouts.app')
@section('title', 'New security audit')
@section('width', 'narrow')
@section('content')
<a class="back" href="{{ route('audits.index') }}"><x-icon name="back" :size="16" />Security audits</a>
<x-page-header title="New security audit" sub="Name the audit and say what it covers. Pick a template to start with a ready-made checklist." />

<form method="POST" action="{{ route('audits.store') }}" class="surface form-card form-stack">@csrf
  <div>
    <label class="form-label" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Q4 server room review">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="scope">Scope</label>
    <textarea id="scope" name="scope" class="form-control @error('scope') is-invalid @enderror" rows="3" placeholder="Which systems, locations or teams does this cover?">{{ old('scope') }}</textarea>
    @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="checklist_template_id">Checklist template <span class="muted fw-normal">(optional)</span></label>
    <select id="checklist_template_id" name="checklist_template_id" class="form-select @error('checklist_template_id') is-invalid @enderror">
      <option value="">No template: record findings freely</option>
      @foreach($templates as $t)<option value="{{ $t->id }}" @selected(old('checklist_template_id') == $t->id)>{{ $t->name }}</option>@endforeach
    </select>
    @error('checklist_template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-hint">The template's questions are copied into this audit. Later edits to the template won't change it.</div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Start audit</button>
    <a class="btn btn-quiet" href="{{ route('audits.index') }}">Cancel</a>
  </div>
</form>
@endsection
