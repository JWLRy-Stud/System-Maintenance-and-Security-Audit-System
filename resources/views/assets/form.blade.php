@extends('layouts.app')
@section('title', $asset->exists ? 'Edit asset' : 'Add asset')
@section('width', 'narrow')
@section('content')
<a class="back" href="{{ $asset->exists ? route('assets.show', $asset) : route('assets.index') }}"><x-icon name="back" :size="16" />{{ $asset->exists ? $asset->asset_tag : 'Assets' }}</a>
<x-page-header :title="$asset->exists ? 'Edit asset' : 'Add asset'" :sub="$asset->exists ? 'Update details for '.$asset->asset_tag.'.' : 'Each asset needs a unique tag so it can be found later.'" />

<form method="POST" action="{{ $asset->exists ? route('assets.update', $asset) : route('assets.store') }}" class="surface form-card">
  @csrf @if($asset->exists) @method('PUT') @endif
  <div class="form-grid">
    @foreach(['asset_tag' => 'Asset tag', 'name' => 'Name', 'category' => 'Category', 'location' => 'Location'] as $f => $l)
    <div>
      <label class="form-label" for="{{ $f }}">{{ $l }}</label>
      <input id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $asset->$f) }}" class="form-control @error($f) is-invalid @enderror">
      @error($f)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @endforeach
    <div>
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach(\App\Models\Asset::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', $asset->status ?: 'active') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
      </select>
      @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <div class="form-hint">Retired assets stay on record but can't receive new maintenance requests.</div>
    </div>
    <div>
      <label class="form-label" for="assigned_user_id">Assigned to</label>
      <select id="assigned_user_id" name="assigned_user_id" class="form-select @error('assigned_user_id') is-invalid @enderror">
        <option value="">Unassigned</option>
        @foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_user_id', $asset->assigned_user_id) == $u->id)>{{ $u->name }}</option>@endforeach
      </select>
      @error('assigned_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Save asset</button>
    <a class="btn btn-quiet" href="{{ $asset->exists ? route('assets.show', $asset) : route('assets.index') }}">Cancel</a>
  </div>
</form>
@endsection
