@extends('layouts.app')
@section('title', 'Change password')
@section('width', 'narrow')
@section('content')
<x-page-header title="Change password" sub="Choose something you don't use anywhere else." />

<form method="POST" action="{{ route('account.password.update') }}" class="surface form-card form-stack">@csrf @method('PUT')
  <div>
    <label class="form-label" for="current_password">Current password</label>
    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="form-label" for="password">New password</label>
    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-hint">At least 10 characters, with upper and lower case letters and a number, and different from your current one.</div>
  </div>
  <div>
    <label class="form-label" for="password_confirmation">Confirm new password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
  </div>
  <div class="form-actions"><button class="btn btn-primary">Update password</button></div>
</form>
@endsection
