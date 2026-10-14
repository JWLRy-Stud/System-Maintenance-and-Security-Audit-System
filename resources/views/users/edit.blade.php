@extends('layouts.app')
@section('title', 'Edit user')
@section('width', 'narrow')
@section('content')
@php $self = $user->id === auth()->id(); @endphp
<a class="back" href="{{ route('users.index') }}"><x-icon name="back" :size="16" />Users</a>
<x-page-header title="Edit user" :sub="$user->email" />

<form method="POST" action="{{ route('users.update', $user) }}" class="surface form-card">@csrf @method('PUT')
  <div class="form-grid">
    <div>
      <label class="form-label" for="name">Name</label>
      <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" @disabled($self)>
        @foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(old('role', $user->role) === $r)><x-role :value="$r" /></option>@endforeach
      </select>
      @if($self)<input type="hidden" name="role" value="{{ $user->role }}"><div class="form-hint">You cannot change your own role.</div>@endif
      @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div>
      <label class="form-label" for="password">Reset password <span class="muted fw-normal">(optional)</span></label>
      <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <div class="form-hint">Leave blank to keep the current password. A new one needs at least 10 characters with upper and lower case letters and a number. Changing the role or password signs the person out everywhere.</div>
    </div>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary">Save changes</button>
    <a class="btn btn-quiet" href="{{ route('users.index') }}">Cancel</a>
  </div>
</form>
@endsection
