@extends('layouts.app')
@section('title', 'Security audits')
@section('content')
<x-page-header title="Security audits" sub="Audits, their checklists and the findings they raise.">
  <x-slot:actions>
    <a class="btn btn-outline-secondary" href="{{ route('reports.findings') }}"><x-icon name="download" :size="16" />Export findings</a>
    <a class="btn btn-primary" href="{{ route('audits.create') }}"><x-icon name="plus" :size="16" />New audit</a>
  </x-slot:actions>
</x-page-header>

@if($audits->isEmpty())
  <div class="surface">
    <x-empty title="No audits yet" hint="Start an audit, optionally from a checklist template, and record what you find.">
      <a class="btn btn-primary" href="{{ route('audits.create') }}">New audit</a>
    </x-empty>
  </div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Audit</th><th>Status</th><th>Findings</th><th>Started</th></tr></thead>
      <tbody>
      @foreach($audits as $a)
        <tr>
          <td><a class="cell-title" href="{{ route('audits.show', $a) }}">{{ $a->title }}</a><span class="cell-sub">Auditor: {{ $a->auditor->name }}</span></td>
          <td><x-status :value="$a->status" /></td>
          <td class="num">{{ $a->findings_count }}</td>
          <td class="muted" title="{{ $a->created_at }}">{{ $a->created_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="pager">{{ $audits->links() }}</div>
@endif
@endsection
