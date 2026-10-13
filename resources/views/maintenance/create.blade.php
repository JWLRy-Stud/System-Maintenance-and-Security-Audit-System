@extends('layouts.app')
@section('title', 'New maintenance request')
@section('width', 'narrow')
@section('content')
<a class="back" href="{{ route('maintenance.index') }}"><x-icon name="back" :size="16" />Maintenance</a>
<x-page-header title="New maintenance request" sub="Describe what needs attention. An administrator will assign a technician." />

<form method="POST" action="{{ route('maintenance.store') }}" class="surface form-card form-stack">@csrf
  <div>
    <label class="form-label" for="asset_id">Asset</label>
    <select id="asset_id" name="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
      @foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', request('asset')) == $a->id)>{{ $a->asset_tag }} - {{ $a->name }}</option>@endforeach
    </select>
    @error('asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="title">What needs attention?</label>
    <input id="title" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="Short summary, e.g. Printer jams on every page">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="description">Details</label>
    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="What happened, and when did it start?">{{ old('description') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <div class="form-label">Priority</div>
    <div class="seg" role="radiogroup" aria-label="Priority">
      @foreach(\App\Models\MaintenanceRequest::PRIORITIES as $p)
        <label><input type="radio" name="priority" value="{{ $p }}" @checked(old('priority', 'medium') === $p)><span>{{ ucfirst($p) }}</span></label>
      @endforeach
    </div>
    @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="scheduled_at">Preferred date and time <span class="muted fw-normal">(optional)</span></label>
    <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-control @error('scheduled_at') is-invalid @enderror">
    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-hint">Requests still open after this time are flagged as overdue.</div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Submit request</button>
    <a class="btn btn-quiet" href="{{ route('maintenance.index') }}">Cancel</a>
  </div>
</form>
@endsection
