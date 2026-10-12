<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Maintenance & Security Audit')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="{{ route('dashboard') }}">Maintenance &amp; Audit</a>
    @auth
    <div class="navbar-nav me-auto">
      <a class="nav-link" href="{{ route('assets.index') }}">Assets</a>
      @if(auth()->user()->hasRole('technician','staff'))<a class="nav-link" href="{{ route('maintenance.index') }}">Maintenance</a>@endif
      @if(auth()->user()->hasRole('auditor'))<a class="nav-link" href="{{ route('audits.index') }}">Security Audits</a>@endif
      @if(auth()->user()->hasRole())<a class="nav-link" href="{{ route('users.index') }}">Users</a><a class="nav-link" href="{{ route('reports.logs') }}">Activity Log</a>@endif
    </div>
    <a class="nav-link text-white-50" href="{{ route('findings.mine') }}">My tasks</a>
    <a class="nav-link text-white-50 me-2" href="{{ route('account.password') }}">Password</a>
    <span class="navbar-text me-3">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Logout</button></form>
    @endauth
  </div>
</nav>
<main class="container pb-5">
  @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
</body>
</html>
