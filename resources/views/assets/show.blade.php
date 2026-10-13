@extends('layouts.app')
@section('title', $asset->asset_tag)
@section('content')
@php $u = auth()->user(); @endphp
<a class="back" href="{{ route('assets.index') }}"><x-icon name="back" :size="16" />Assets</a>

<x-page-header :title="$asset->name" :sub="$asset->asset_tag">
  <x-slot:actions>
    @if($u->hasRole('technician', 'staff') && $asset->status !== 'retired')
      <a class="btn btn-outline-secondary" href="{{ route('maintenance.create', ['asset' => $asset->id]) }}"><x-icon name="wrench" :size="16" />Report an issue</a>
    @endif
    @if($u->hasRole())<a class="btn btn-primary" href="{{ route('assets.edit', $asset) }}">Edit asset</a>@endif
  </x-slot:actions>
</x-page-header>

<div class="surface surface-pad">
  <dl class="meta">
    <div><dt>Status</dt><dd><x-status :value="$asset->status" /></dd></div>
    <div><dt>Category</dt><dd>{{ $asset->category ?: '-' }}</dd></div>
    <div><dt>Location</dt><dd>{{ $asset->location ?: '-' }}</dd></div>
    <div><dt>Assigned to</dt><dd>{{ $asset->assignedUser?->name ?? 'Unassigned' }}</dd></div>
  </dl>
</div>

@if($u->hasRole('technician'))
<div class="section">
  <h2 class="section-title">Maintenance history</h2>
  @if($asset->maintenanceRequests->isEmpty())
    <div class="surface"><x-empty title="No maintenance yet" hint="Requests raised for this asset will be listed here." /></div>
  @else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Request</th><th>Status</th><th>Raised</th></tr></thead>
      <tbody>
      @foreach($asset->maintenanceRequests as $m)
        <tr>
          <td><a class="cell-title" href="{{ route('maintenance.show', $m) }}">{{ $m->title }}</a></td>
          <td><x-status :value="$m->status" /></td>
          <td class="muted" title="{{ $m->created_at }}">{{ $m->created_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endif
@endsection
