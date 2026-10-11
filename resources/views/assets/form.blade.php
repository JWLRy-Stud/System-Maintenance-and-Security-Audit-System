@extends('layouts.app')
@section('content')
<h3>{{ $asset->exists ? 'Edit' : 'New' }} asset</h3>
<form method="POST" action="{{ $asset->exists ? route('assets.update',$asset) : route('assets.store') }}" class="card card-body">
  @csrf @if($asset->exists) @method('PUT') @endif
  @foreach(['asset_tag'=>'Asset tag','name'=>'Name','category'=>'Category','location'=>'Location'] as $f=>$l)
  <div class="mb-3"><label class="form-label">{{ $l }}</label><input name="{{ $f }}" value="{{ old($f,$asset->$f) }}" class="form-control"></div>@endforeach
  <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(\App\Models\Asset::STATUSES as $s)<option value="{{ $s }}" @selected(old('status',$asset->status ?: 'active')===$s)>{{ $s }}</option>@endforeach</select></div>
  <div class="mb-3"><label class="form-label">Assigned user</label><select name="assigned_user_id" class="form-select"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_user_id',$asset->assigned_user_id)==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
