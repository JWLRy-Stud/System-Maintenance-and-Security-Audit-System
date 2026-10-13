@extends('layouts.app')
@section('title', 'Maintenance')
@section('content')
@php
  $current = request('status');
  $tabs = ['' => 'All', 'open' => 'Open', 'assigned' => 'Assigned', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

<x-page-header title="Maintenance" sub="Requests for repairs and routine work, from first report to completion.">
  <x-slot:actions><a href="{{ route('maintenance.create') }}" class="btn btn-primary"><x-icon name="plus" :size="16" />New request</a></x-slot:actions>
</x-page-header>

<nav class="chips" aria-label="Filter by status">
  @foreach($tabs as $value => $label)
    <a class="chip {{ (string) $current === (string) $value ? 'is-active' : '' }}" href="{{ route('maintenance.index', $value === '' ? [] : ['status' => $value]) }}">{{ $label }}</a>
  @endforeach
</nav>

@if($items->isEmpty())
  <div class="surface">
    <x-empty :title="$current ? 'Nothing here' : 'No requests yet'" :hint="$current ? 'No requests have this status right now.' : 'When something needs fixing, report it and it will be tracked here.'">
      @if(!$current)<a href="{{ route('maintenance.create') }}" class="btn btn-primary">New request</a>@endif
    </x-empty>
  </div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Request</th><th>Priority</th><th>Status</th><th>Technician</th><th>Raised</th></tr></thead>
      <tbody>
      @foreach($items as $m)
        <tr>
          <td>
            <a class="cell-title" href="{{ route('maintenance.show', $m) }}">{{ $m->title }}</a>
            <span class="cell-sub">{{ $m->asset->asset_tag }} &middot; {{ $m->asset->name }}</span>
          </td>
          <td><x-status :value="$m->priority" /></td>
          <td><x-status :value="$m->status" /></td>
          <td>{{ $m->technician?->name ?? 'Not assigned' }}</td>
          <td class="muted" title="{{ $m->created_at }}">{{ $m->created_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="pager">{{ $items->links() }}</div>
@endif
@endsection
