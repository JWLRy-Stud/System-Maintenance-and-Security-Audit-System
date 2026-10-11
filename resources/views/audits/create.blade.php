@extends('layouts.app')
@section('content')
<h3>New security audit</h3>
<form method="POST" action="{{ route('audits.store') }}" class="card card-body">@csrf
  <div class="mb-3"><label class="form-label">Title</label><input name="title" value="{{ old('title') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Scope</label><textarea name="scope" class="form-control" rows="3">{{ old('scope') }}</textarea></div>
  <button class="btn btn-primary">Start audit</button>
</form>
@endsection
