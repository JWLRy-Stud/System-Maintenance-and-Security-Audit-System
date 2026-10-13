@props(['title', 'hint' => null])
<div class="empty">
  <p class="empty-title">{{ $title }}</p>
  @if($hint)<p class="empty-hint">{{ $hint }}</p>@endif
  @if(trim((string) $slot) !== '')<div class="empty-actions">{{ $slot }}</div>@endif
</div>
