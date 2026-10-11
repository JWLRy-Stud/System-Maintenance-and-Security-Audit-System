@extends('layouts.app')
@section('content')
<h3>New maintenance request</h3>
<form method="POST" action="{{ route('maintenance.store') }}" class="card card-body">@csrf
  <div class="mb-3"><label class="form-label">Asset</label><select name="asset_id" class="form-select">@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id')==$a->id)>{{ $a->asset_tag }} — {{ $a->name }}</option>@endforeach</select></div>
  <div class="mb-3"><label class="form-label">Title</label><input name="title" value="{{ old('title') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
  <div class="mb-3"><label class="form-label">Priority</label><select name="priority" class="form-select">@foreach(\App\Models\MaintenanceRequest::PRIORITIES as $p)<option value="{{ $p }}" @selected(old('priority','medium')===$p)>{{ $p }}</option>@endforeach</select></div>
  <div class="mb-3"><label class="form-label">Scheduled for (optional)</label><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-control"></div>
  <button class="btn btn-primary">Submit</button>
</form>
@endsection
