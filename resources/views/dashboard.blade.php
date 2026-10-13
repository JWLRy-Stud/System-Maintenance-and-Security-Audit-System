@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
  $u = auth()->user();
  $first = \Illuminate\Support\Str::before($u->name, ' ');
  $maintHref = $u->hasRole('technician', 'staff') ? route('maintenance.index') : null;
  $findHref = $u->hasRole('auditor') ? route('audits.index') : route('findings.mine');
  $stats = [
    ['label' => 'Assets tracked',      'value' => $assets,   'note' => 'In the register',                                   'href' => route('assets.index'), 'alert' => false],
    ['label' => 'Pending maintenance', 'value' => $pending,  'note' => 'Not yet completed',                     'href' => $maintHref,           'alert' => false],
    ['label' => 'Overdue maintenance', 'value' => $overdue,  'note' => $overdue > 0 ? 'Past scheduled date' : 'Nothing past due', 'href' => $maintHref, 'alert' => $overdue > 0],
    ['label' => 'Open findings',       'value' => $findings, 'note' => 'Still being fixed',                               'href' => $findHref,            'alert' => false],
  ];
@endphp

<x-page-header :title="'Welcome back, '.$first" sub="Here is where things stand across your assets, maintenance and audits." />

<div class="stats">
  @foreach($stats as $s)
    @if($s['href'])
      <a class="stat {{ $s['alert'] ? 'is-alert' : '' }}" href="{{ $s['href'] }}">
    @else
      <div class="stat {{ $s['alert'] ? 'is-alert' : '' }}">
    @endif
      <div class="stat-num">{{ $s['value'] }}</div>
      <div class="stat-label">{{ $s['label'] }}</div>
      <div class="stat-note">{{ $s['note'] }}</div>
    @if($s['href']) </a> @else </div> @endif
  @endforeach
</div>

<div class="section {{ $recent->isNotEmpty() ? 'split' : '' }}">
  @if($recent->isNotEmpty())
  <div>
    <h2 class="section-title">Recent activity</h2>
    <div class="surface">
      <ul class="feed">
        @foreach($recent as $l)
        <li>
          <span class="avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($l->actor?->name ?? 'S', 0, 1)) }}</span>
          <div class="feed-text"><strong>{{ $l->actor?->name ?? 'System' }}</strong> <span class="muted">{{ $l->action }}</span></div>
          <x-status :value="$l->outcome" />
          <span class="feed-time" title="{{ $l->created_at }}">{{ \Illuminate\Support\Carbon::parse($l->created_at)->diffForHumans() }}</span>
        </li>
        @endforeach
      </ul>
    </div>
    <p class="mt-3"><a href="{{ route('reports.logs') }}">View the full activity log</a></p>
  </div>
  @endif

  <div>
    <h2 class="section-title">Quick actions</h2>
    <div class="surface quick">
      @if($u->hasRole('technician', 'staff'))
        <a class="btn btn-outline-secondary" href="{{ route('maintenance.create') }}"><x-icon name="wrench" :size="16" />Report an issue</a>
      @endif
      @if($u->hasRole('auditor'))
        <a class="btn btn-outline-secondary" href="{{ route('audits.create') }}"><x-icon name="shield" :size="16" />Start an audit</a>
      @endif
      @if($u->hasRole())
        <a class="btn btn-outline-secondary" href="{{ route('assets.create') }}"><x-icon name="box" :size="16" />Add an asset</a>
        <a class="btn btn-outline-secondary" href="{{ route('users.create') }}"><x-icon name="users" :size="16" />Add a user</a>
      @endif
      <a class="btn btn-outline-secondary" href="{{ route('findings.mine') }}"><x-icon name="task" :size="16" />See my tasks</a>
    </div>
  </div>
</div>
@endsection
