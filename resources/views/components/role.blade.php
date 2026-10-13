@props(['value'])
@php
$labels = ['admin' => 'Administrator', 'technician' => 'Technician', 'auditor' => 'Security auditor', 'staff' => 'Staff'];
@endphp
{{ $labels[$value] ?? ucfirst((string) $value) }}
