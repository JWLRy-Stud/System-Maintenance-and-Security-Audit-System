@props(['finding'])
@php
$labels = ['in_progress' => 'Start work', 'resolved' => 'Mark resolved', 'accepted_risk' => 'Accept risk', 'open' => 'Reopen'];
@endphp
@foreach(\App\Models\AuditFinding::TRANSITIONS[$finding->status] as $to)
<form method="POST" action="{{ route('findings.update', $finding) }}">@csrf @method('PATCH')
  <input type="hidden" name="status" value="{{ $to }}">
  <button class="btn btn-sm {{ $to === 'resolved' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $labels[$to] ?? ucfirst(str_replace('_', ' ', $to)) }}</button>
</form>
@endforeach
