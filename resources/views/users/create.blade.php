@extends('layouts.app')
@section('content')
<h3>New user</h3>
<form method="POST" action="{{ route('users.store') }}" class="card card-body">@csrf
  <div class="mb-3"><label class="form-label">Name</label><input name="name" value="{{ old('name') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Password (min 8)</label><input type="password" name="password" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Role</label><select name="role" class="form-select">@foreach(\App\Models\User::ROLES as $r)<option>{{ $r }}</option>@endforeach</select></div>
  <button class="btn btn-primary">Create</button>
</form>
@endsection
