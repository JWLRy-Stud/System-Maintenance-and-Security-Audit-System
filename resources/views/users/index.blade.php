@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-page-header title="Users" sub="Who can sign in, and what each person can do.">
  <x-slot:actions><a class="btn btn-primary" href="{{ route('users.create') }}"><x-icon name="plus" :size="16" />Add user</a></x-slot:actions>
</x-page-header>

<div class="table-wrap">
  <table class="data">
    <thead><tr><th>Person</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
      <tr>
        <td>
          <div class="d-flex align-items-center gap-3">
            <span class="avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($u->name, 0, 1)) }}</span>
            <div><span class="cell-title">{{ $u->name }}</span><span class="cell-sub">{{ $u->email }}</span></div>
          </div>
        </td>
        <td><x-role :value="$u->role" /></td>
        <td><x-status :value="$u->status" /></td>
        <td class="cell-actions">
          <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', $u) }}">Edit</a>
          @if($u->id !== auth()->id())
          <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf
            <button class="btn btn-sm {{ $u->status === 'active' ? 'btn-danger-quiet' : 'btn-outline-secondary' }}">{{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
          </form>
          @else
            <span class="muted small-text">You</span>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
<div class="pager">{{ $users->links() }}</div>
@endsection
