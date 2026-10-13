@props(['value'])
@php
$v = (string) $value;
$tones = [
  'open' => 'info', 'assigned' => 'info', 'in_progress' => 'warn',
  'completed' => 'success', 'resolved' => 'success',
  'cancelled' => 'muted', 'accepted_risk' => 'muted',
  'active' => 'success', 'under_maintenance' => 'warn', 'retired' => 'muted', 'inactive' => 'muted',
  'low' => 'neutral', 'medium' => 'info', 'high' => 'warn', 'critical' => 'danger',
  'pass' => 'success', 'fail' => 'danger', 'not_applicable' => 'muted', 'pending' => 'neutral',
  'success' => 'success', 'denied' => 'warn', 'failed' => 'danger',
];
$labels = ['not_applicable' => 'N/A'];
$tone = $tones[$v] ?? 'neutral';
$label = $labels[$v] ?? ucfirst(str_replace('_', ' ', $v));
@endphp
<span class="pill pill-{{ $tone }}">{{ $label }}</span>
