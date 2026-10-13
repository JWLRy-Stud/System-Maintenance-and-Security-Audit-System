@props(['title', 'sub' => null])
<header class="page-head">
  <div>
    <h1 class="page-title">{{ $title }}</h1>
    @if($sub)<p class="page-sub">{{ $sub }}</p>@endif
  </div>
  @isset($actions)<div class="page-actions">{{ $actions }}</div>@endisset
</header>
