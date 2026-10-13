@extends('layouts.app')
@section('title', 'My tasks')
@section('content')
<x-page-header title="My tasks" sub="Security findings assigned to you for remediation." />

@if($findings->isEmpty())
  <div class="surface"><x-empty title="Nothing assigned to you" hint="Findings assigned to you will show up here, with their due dates." /></div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Finding</th><th>Severity</th><th>Status</th><th>Due</th><th></th></tr></thead>
      <tbody>
      @foreach($findings as $f)
        @php $late = $f->due_date && $f->due_date->copy()->endOfDay()->isPast() && in_array($f->status, ['open', 'in_progress']); @endphp
        <tr>
          <td><span class="cell-title">{{ $f->title }}</span><span class="cell-sub">{{ $f->audit->title }}@if($f->description) &middot; {{ $f->description }}@endif</span></td>
          <td><x-status :value="$f->severity" /></td>
          <td><x-status :value="$f->status" /></td>
          <td class="nowrap {{ $late ? 'is-overdue' : '' }}">{{ $f->due_date?->format('j M Y') ?? '-' }}@if($late) <span class="visually-hidden">(overdue)</span>@endif</td>
          <td class="cell-actions"><x-finding-actions :finding="$f" /></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="pager">{{ $findings->links() }}</div>
@endif
@endsection
