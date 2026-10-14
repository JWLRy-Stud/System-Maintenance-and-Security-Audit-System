<!doctype html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@hasSection('title')@yield('title') | @endif Maintenance &amp; Security Audit</title>
  <script>
    // Dark is the default; apply the saved choice before first paint to avoid a flash.
    (function () {
      var t = 'dark';
      try { t = localStorage.getItem('theme') || 'dark'; } catch (e) {}
      document.documentElement.setAttribute('data-theme', t);
      document.documentElement.setAttribute('data-bs-theme', t);
    })();
  </script>
  {{-- BUG B-17 [OPEN]: Bootstrap/fonts load from CDNs without Subresource Integrity. FIX: add integrity + crossorigin attributes or self-host (see docs/KNOWN_BUGS.md). --}}
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=Newsreader:opsz,wght@6..72,400;6..72,500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/ui.css') }}?v={{ @filemtime(public_path('css/ui.css')) }}" rel="stylesheet">
</head>
<body>
@auth
@php
  $u = auth()->user();
  $nav = fn (string $pattern) => request()->routeIs($pattern) ? 'is-active' : '';
@endphp
<div class="app">
  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="brand" href="{{ route('dashboard') }}">
      <span class="brand-mark"><x-icon name="shield" :size="18" /></span>
      <span>Maintenance &amp; Audit</span>
    </a>

    <nav class="nav-groups">
      <a class="nav-item {{ $nav('dashboard') }}" href="{{ route('dashboard') }}"><x-icon name="home" />Dashboard</a>
      <a class="nav-item {{ $nav('assets.*') }}" href="{{ route('assets.index') }}"><x-icon name="box" />Assets</a>

      <div class="nav-label">Work</div>
      @if($u->hasRole('technician', 'staff'))
        <a class="nav-item {{ $nav('maintenance.*') }}" href="{{ route('maintenance.index') }}"><x-icon name="wrench" />Maintenance</a>
      @endif
      <a class="nav-item {{ $nav('findings.mine') }}" href="{{ route('findings.mine') }}"><x-icon name="task" />My tasks</a>

      @if($u->hasRole('auditor'))
        <div class="nav-label">Security</div>
        <a class="nav-item {{ $nav('audits.*') }}" href="{{ route('audits.index') }}"><x-icon name="shield" />Security audits</a>
        <a class="nav-item {{ $nav('checklists.*') }}" href="{{ route('checklists.index') }}"><x-icon name="list" />Checklists</a>
      @endif

      @if($u->isAdmin())
        <div class="nav-label">Administration</div>
        <a class="nav-item {{ $nav('users.*') }}" href="{{ route('users.index') }}"><x-icon name="users" />Users</a>
        <a class="nav-item {{ $nav('reports.logs') }}" href="{{ route('reports.logs') }}"><x-icon name="activity" />Activity log</a>
      @endif
    </nav>

    <div class="side-foot">
      <div class="who">
        <span class="avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($u->name, 0, 1)) }}</span>
        <div>
          <div class="who-name">{{ $u->name }}</div>
          <div class="who-role"><x-role :value="$u->role" /></div>
        </div>
      </div>
      <x-theme-toggle />
      <a class="nav-item {{ $nav('account.*') }}" href="{{ route('account.password') }}"><x-icon name="key" />Change password</a>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="nav-item" type="submit"><x-icon name="logout" />Sign out</button>
      </form>
    </div>
  </aside>
  <div class="scrim" data-nav-toggle></div>

  <div>
    <div class="topbar">
      <button class="icon-btn" type="button" data-nav-toggle aria-label="Open menu" aria-controls="sidebar"><x-icon name="menu" /></button>
      <a class="brand" href="{{ route('dashboard') }}">Maintenance &amp; Audit</a>
    </div>
    <main class="main">
      <div class="content @yield('width')">
        @include('layouts.notices')
        @yield('content')
      </div>
    </main>
  </div>
</div>
@else
<main class="auth-shell">
  <x-theme-toggle class="icon-btn theme-fab" />
  <div class="auth-card">
    @include('layouts.notices')
    @yield('content')
  </div>
</main>
@endauth

<script>
  document.querySelectorAll('[data-nav-toggle]').forEach(function (el) {
    el.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
  });
  document.querySelectorAll('[data-theme-toggle]').forEach(function (el) {
    el.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      document.documentElement.setAttribute('data-bs-theme', next);
      try { localStorage.setItem('theme', next); } catch (e) {}
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.body.classList.remove('nav-open');
  });
</script>
@stack('scripts')
</body>
</html>
