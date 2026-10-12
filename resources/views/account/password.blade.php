@extends('layouts.app')
@section('content')
<h3>Change password</h3>
<form method="POST" action="{{ route('account.password.update') }}" class="card card-body" style="max-width:480px">@csrf @method('PUT')
  <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">New password (min 8)</label><input type="password" name="password" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" class="form-control" required></div>
  <button class="btn btn-primary">Update password</button>
</form>
@endsection
