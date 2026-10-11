@extends('layouts.app')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body">
  <h4 class="mb-3">Sign in</h4>
  <form method="POST" action="/login">@csrf
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-primary w-100">Login</button>
  </form>
</div></div></div></div>
@endsection
