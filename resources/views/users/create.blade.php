@extends('layouts.app')
@section('title', 'Add user')
@section('width', 'narrow')
@section('content')
<a class="back" href="{{ route('users.index') }}"><x-icon name="back" :size="16" />Users</a>
<x-page-header title="Add user" sub="They can sign in straight away with the password you set here, and will be asked to replace it first." />

<form method="POST" action="{{ route('users.store') }}" class="surface form-card">@csrf
  <div class="form-grid">
    <div>
      <label class="form-label" for="name">Name</label>
      <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="password">Password</label>
      <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <div class="form-hint">At least 10 characters, with upper and lower case letters and a number.</div>
    </div>
    <div>
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
        @foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(old('role') === $r)><x-role :value="$r" /></option>@endforeach
      </select>
      @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Create user</button>
    <a class="btn btn-quiet" href="{{ route('users.index') }}">Cancel</a>
  </div>
</form>
@endsection
