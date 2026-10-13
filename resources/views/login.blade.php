@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<a class="brand" href="/login"><span class="brand-mark"><x-icon name="shield" :size="18" /></span><span>Maintenance &amp; Audit</span></a>
<h1 class="auth-title">Welcome back</h1>
<p class="auth-sub">Sign in to manage assets, maintenance and security audits.</p>
<form method="POST" action="/login" class="surface surface-pad form-stack">@csrf
  <div>
    <label class="form-label" for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
  </div>
  <div>
    <label class="form-label" for="password">Password</label>
    <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
  </div>
  <button class="btn btn-primary w-100">Sign in</button>
</form>
@endsection
