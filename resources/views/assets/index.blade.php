@extends('layouts.app')
@section('title', 'Assets')
@section('content')
@php $filtered = request()->filled('q') || request()->filled('status'); @endphp

<x-page-header title="Assets" :sub="$assets->total().' '.\Illuminate\Support\Str::plural('asset', $assets->total()).($filtered ? ' match your filters.' : ' in the register.')">
  @if(auth()->user()->hasRole())
  <x-slot:actions><a href="{{ route('assets.create') }}" class="btn btn-primary"><x-icon name="plus" :size="16" />Add asset</a></x-slot:actions>
  @endif
</x-page-header>

<form class="toolbar" method="GET">
  <div class="search">
    <x-icon name="search" :size="16" />
    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by tag or name" aria-label="Search assets">
  </div>
  <select name="status" class="form-select" aria-label="Filter by status">
    <option value="">All statuses</option>
    @foreach(\App\Models\Asset::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
  </select>
  <button class="btn btn-outline-secondary">Apply</button>
  @if($filtered)<a href="{{ route('assets.index') }}" class="btn btn-quiet">Clear</a>@endif
</form>

@if($assets->isEmpty())
  <div class="surface">
    @if($filtered)
      <x-empty title="No assets match" hint="Try a different tag or name, or clear the filters to see everything." />
    @else
      <x-empty title="No assets yet" hint="Assets are the equipment you maintain and audit. Add the first one to get started.">
        @if(auth()->user()->hasRole())<a href="{{ route('assets.create') }}" class="btn btn-primary">Add asset</a>@endif
      </x-empty>
    @endif
  </div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Tag</th><th>Name</th><th>Category</th><th>Location</th><th>Status</th></tr></thead>
      <tbody>
      @foreach($assets as $a)
        <tr>
          <td><a class="tag" href="{{ route('assets.show', $a) }}">{{ $a->asset_tag }}</a></td>
          <td><a class="cell-title" href="{{ route('assets.show', $a) }}">{{ $a->name }}</a></td>
          <td>{{ $a->category ?: '-' }}</td>
          <td>{{ $a->location ?: '-' }}</td>
          <td><x-status :value="$a->status" /></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="pager">{{ $assets->links() }}</div>
@endif
@endsection
