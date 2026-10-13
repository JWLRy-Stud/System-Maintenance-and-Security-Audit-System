@extends('layouts.app')
@section('title', 'Activity log')
@section('content')
@php $filtered = request()->filled('action') || request()->filled('outcome'); @endphp
<x-page-header title="Activity log" sub="A permanent record of who did what, and whether it worked." />

<form class="toolbar" method="GET">
  <div class="search">
    <x-icon name="search" :size="16" />
    <input name="action" value="{{ request('action') }}" class="form-control" placeholder="Action, e.g. maintenance" aria-label="Filter by action">
  </div>
  <select name="outcome" class="form-select" aria-label="Filter by outcome">
    <option value="">Any outcome</option>
    @foreach(['success', 'denied', 'failed'] as $o)<option value="{{ $o }}" @selected(request('outcome') === $o)>{{ ucfirst($o) }}</option>@endforeach
  </select>
  <button class="btn btn-outline-secondary">Apply</button>
  @if($filtered)<a href="{{ route('reports.logs') }}" class="btn btn-quiet">Clear</a>@endif
</form>

@if($logs->isEmpty())
  <div class="surface"><x-empty title="No matching activity" hint="Try a broader action prefix or clear the filters." /></div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Outcome</th><th>Request ID</th></tr></thead>
      <tbody>
      @foreach($logs as $l)
        <tr>
          <td class="muted" style="white-space: nowrap" title="{{ $l->created_at }}">{{ \Illuminate\Support\Carbon::parse($l->created_at)->diffForHumans() }}</td>
          <td>{{ $l->actor?->name ?? 'System' }}</td>
          <td>
            @if(str_contains($l->action, '.'))
              <span class="muted">{{ \Illuminate\Support\Str::before($l->action, '.') }}.</span>{{ \Illuminate\Support\Str::after($l->action, '.') }}
            @else
              {{ $l->action }}
            @endif
          </td>
          <td>@if($l->subject_type){{ class_basename($l->subject_type) }} #{{ $l->subject_id }}@else <span class="muted">-</span>@endif</td>
          <td><x-status :value="$l->outcome" /></td>
          <td class="muted small-text num">{{ $l->request_id ? \Illuminate\Support\Str::limit($l->request_id, 8, '') : '-' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="pager">{{ $logs->links() }}</div>
@endif
@endsection
