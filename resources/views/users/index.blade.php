@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between mb-3"><h3>Users</h3><a class="btn btn-primary" href="{{ route('users.create') }}">New user</a></div>
<table class="table table-sm table-striped bg-white"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role }}</td><td>{{ $u->status }}</td>
<td>@if($u->id!==auth()->id())<form method="POST" action="{{ route('users.toggle',$u) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $u->status==='active'?'Deactivate':'Activate' }}</button></form>@endif</td></tr>@endforeach</tbody></table>
{{ $users->links() }}
@endsection
