@extends('layouts.app')
@section('title', $audit->title)
@section('content')
@php
  $done = $audit->status === 'completed';
  $canManage = auth()->user()->can('manage', $audit); // owner or admin; other auditors get a read-only view
  $total = $audit->checks->count();
  $answered = $audit->checks->where('result', '!=', 'pending')->count();
  $pct = $total ? round($answered / $total * 100) : 0;
@endphp
<a class="back" href="{{ route('audits.index') }}"><x-icon name="back" :size="16" />Security audits</a>

<x-page-header :title="$audit->title" :sub="$audit->scope">
  <x-slot:actions>
    <x-status :value="$audit->status" />
    @if(! $done && $canManage)
    <form method="POST" action="{{ route('audits.complete', $audit) }}">@csrf
      <button class="btn btn-primary" onclick="return confirm('Complete this audit? Checks can no longer be changed afterwards.')">Complete audit</button>
    </form>
    @endif
  </x-slot:actions>
</x-page-header>

<div class="surface surface-pad">
  <dl class="meta">
    <div><dt>Auditor</dt><dd>{{ $audit->auditor->name }}</dd></div>
    <div><dt>Findings</dt><dd>{{ $audit->findings->count() }}</dd></div>
    @if($total)<div><dt>Checklist</dt><dd>{{ $answered }} of {{ $total }} answered</dd></div>@endif
  </dl>
</div>

@if($total)
<div class="section">
  <h2 class="section-title">Checklist</h2>
  <div class="meter mb-3" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Checklist progress"><span style="width: {{ $pct }}%"></span></div>
  <div class="surface">
    @foreach($audit->checks as $c)
    <div class="check">
      <div class="check-q">
        <span>{{ $c->question }}</span>
      </div>
      @if($done || ! $canManage)
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <x-status :value="$c->result" />
          @if($c->notes)<span class="muted small-text">{{ $c->notes }}</span>@endif
          @if($c->finding_id)<span class="small-text" style="color: var(--clay-text)">Finding raised</span>@endif
        </div>
      @else
        <form method="POST" action="{{ route('checks.update', $c) }}" class="check-form" data-check>@csrf @method('PATCH')
          <div class="seg" role="radiogroup" aria-label="Result">
            <label class="seg-pass"><input type="radio" name="result" value="pass" required @checked($c->result === 'pass')><span>Pass</span></label>
            <label class="seg-fail"><input type="radio" name="result" value="fail" @checked($c->result === 'fail')><span>Fail</span></label>
            <label class="seg-na"><input type="radio" name="result" value="not_applicable" @checked($c->result === 'not_applicable')><span>N/A</span></label>
          </div>
          <select name="severity" class="form-select form-select-sm" data-severity aria-label="Severity if this fails" @if($c->result !== 'fail') hidden @endif>
            @foreach(\App\Models\AuditFinding::SEVERITIES as $sv)<option value="{{ $sv }}" @selected($sv === 'medium')>{{ ucfirst($sv) }} severity</option>@endforeach
          </select>
          <input name="notes" value="{{ $c->notes }}" class="form-control form-control-sm notes" placeholder="Notes or evidence reference" aria-label="Notes">
          <button class="btn btn-sm btn-outline-secondary">Save</button>
          @if($c->finding_id)<span class="small-text" style="color: var(--clay-text)">Finding raised</span>@endif
        </form>
      @endif
    </div>
    @endforeach
  </div>
</div>
@endif

<div class="section">
  <h2 class="section-title">Findings</h2>
  @if($audit->findings->isEmpty())
    <div class="surface"><x-empty title="No findings yet" :hint="$done ? 'This audit was completed without findings.' : 'Failed checks raise findings automatically. You can also record one by hand below.'" /></div>
  @else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Finding</th><th>Severity</th><th>Status</th><th>Owner</th><th>Due</th><th></th></tr></thead>
      <tbody>
      @foreach($audit->findings as $f)
        @php $late = $f->due_date && $f->due_date->copy()->endOfDay()->isPast() && in_array($f->status, ['open', 'in_progress']); @endphp
        <tr>
          <td><span class="cell-title">{{ $f->title }}</span>@if($f->description)<span class="cell-sub">{{ $f->description }}</span>@endif</td>
          <td><x-status :value="$f->severity" /></td>
          <td><x-status :value="$f->status" /></td>
          <td class="nowrap">{{ $f->assignee?->name ?? 'Unassigned' }}</td>
          <td class="nowrap {{ $late ? 'is-overdue' : '' }}">{{ $f->due_date?->format('j M Y') ?? '-' }}</td>
          <td class="cell-actions">
            <x-finding-actions :finding="$f" />
            @can('assign', $f)
              @if(in_array($f->status, ['open', 'in_progress']))<x-finding-assign :finding="$f" :users="$users" />@endif
            @endcan
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>

@if(! $done && $canManage)
<div class="section">
  <h2 class="section-title">Record a finding</h2>
  <form method="POST" action="{{ route('findings.store', $audit) }}" class="surface form-card">@csrf
    <div class="form-grid">
      <div class="span-2">
        <label class="form-label" for="f_title">Title</label>
        <input id="f_title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Admin port exposed to the internet">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="span-2">
        <label class="form-label" for="f_desc">Description or evidence</label>
        <textarea id="f_desc" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
      </div>
      <div>
        <label class="form-label" for="f_sev">Severity</label>
        <select id="f_sev" name="severity" class="form-select">@foreach(\App\Models\AuditFinding::SEVERITIES as $s)<option value="{{ $s }}" @selected(old('severity', 'medium') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
      </div>
      <div>
        <label class="form-label" for="f_due">Due date</label>
        <input id="f_due" type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
      </div>
      <div class="span-2">
        <label class="form-label" for="f_owner">Who fixes it?</label>
        <select id="f_owner" name="assigned_to" class="form-select"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_to') == $u->id)>{{ $u->name }}</option>@endforeach</select>
      </div>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Add finding</button></div>
  </form>
</div>
@endif

@push('scripts')
<script>
  // Show the severity picker only when a check is marked "Fail".
  document.querySelectorAll('[data-check]').forEach(function (form) {
    var sev = form.querySelector('[data-severity]');
    form.addEventListener('change', function (e) {
      if (e.target.name === 'result') sev.hidden = e.target.value !== 'fail';
    });
  });
</script>
@endpush
@endsection
