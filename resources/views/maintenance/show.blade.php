@extends('layouts.app')
@section('title', $req->title)
@section('content')
@php
  $u = auth()->user();
  $next = \App\Models\MaintenanceRequest::TRANSITIONS[$req->status];
  $isAdmin = $u->role === 'admin';
  $isTech = $req->technician_id === $u->id;
  $canAssign = $req->status === 'open' && $isAdmin;
  $canStart = $req->status === 'assigned' && ($isAdmin || $isTech);
  $canComplete = $req->status === 'in_progress' && ($isAdmin || $isTech);
  $canCancel = in_array('cancelled', $next) && ($isAdmin || ($req->requester_id === $u->id && $req->status === 'open'));
  $forward = $canAssign || $canStart || $canComplete;

  $flow = ['open' => 'Open', 'assigned' => 'Assigned', 'in_progress' => 'In progress', 'completed' => 'Completed'];
  $keys = array_keys($flow);
  $pos = array_search($req->status, $keys);
  $void = $req->status === 'cancelled';
@endphp
<a class="back" href="{{ route('maintenance.index') }}"><x-icon name="back" :size="16" />Maintenance</a>

<x-page-header :title="$req->title">
  <x-slot:actions><x-status :value="$req->status" /></x-slot:actions>
</x-page-header>

<div class="surface mb-4">
  <ol class="steps {{ $void ? 'is-void' : '' }}" aria-label="Progress">
    @foreach($flow as $key => $label)
      @php $i = array_search($key, $keys); $state = $void ? '' : ($i < $pos ? 'is-done' : ($i === $pos ? 'is-current' : '')); @endphp
      <li class="step {{ $state }}" @if($state === 'is-current') aria-current="step" @endif><span class="dot"></span><span>{{ $label }}</span></li>
    @endforeach
  </ol>
  @if($void)<div class="px-4 pb-3 small-text muted">This request was cancelled.</div>@endif
</div>

<div class="split">
  <div>
    <div class="surface surface-pad">
      <dl class="meta">
        <div><dt>Asset</dt><dd><a class="tag" href="{{ route('assets.show', $req->asset) }}">{{ $req->asset->asset_tag }}</a> {{ $req->asset->name }}</dd></div>
        <div><dt>Priority</dt><dd><x-status :value="$req->priority" /></dd></div>
        <div><dt>Requested by</dt><dd>{{ $req->requester->name }}</dd></div>
        <div><dt>Technician</dt><dd>{{ $req->technician?->name ?? 'Not assigned' }}</dd></div>
        @if($req->scheduled_at)<div><dt>Scheduled for</dt><dd>{{ $req->scheduled_at->format('j M Y, H:i') }}</dd></div>@endif
        <div><dt>Raised</dt><dd>{{ $req->created_at->format('j M Y, H:i') }}</dd></div>
      </dl>
      @if($req->description)
        <hr class="my-4" style="border-color: var(--line); opacity: 1;">
        <p class="prose mb-0">{{ $req->description }}</p>
      @endif
    </div>

    @if($req->records->isNotEmpty())
    <div class="section">
      <h2 class="section-title">Completion record</h2>
      <div class="surface">
        @foreach($req->records as $r)
        <div class="record">
          <div class="record-head">
            <span class="avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($r->performer->name, 0, 1)) }}</span>
            <div><strong>{{ $r->performer->name }}</strong><div class="muted small-text">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M Y, H:i') }}</div></div>
          </div>
          <p class="prose mb-1">{{ $r->work_summary }}</p>
          @if($r->completion_notes)<p class="prose muted small-text mb-0">{{ $r->completion_notes }}</p>@endif
        </div>
        @endforeach
      </div>
    </div>
    @endif
  </div>

  <aside class="surface surface-pad">
    <h2 class="section-title">Next step</h2>

    @if($canAssign)
      <form method="POST" action="{{ route('maintenance.transition', $req) }}" class="form-stack">@csrf
        <input type="hidden" name="status" value="assigned">
        <div>
          <label class="form-label" for="technician_id">Assign a technician</label>
          <select id="technician_id" name="technician_id" class="form-select" @disabled($technicians->isEmpty())>
            @foreach($technicians as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
          </select>
          @if($technicians->isEmpty())<div class="form-hint">There are no active technicians. <a href="{{ route('users.create') }}">Add one</a> first.</div>@endif
        </div>
        <button class="btn btn-primary w-100" @disabled($technicians->isEmpty())>Assign technician</button>
      </form>
    @endif

    @if($canStart)
      <p class="muted">The technician is ready to begin work on this request.</p>
      <form method="POST" action="{{ route('maintenance.transition', $req) }}">@csrf
        <input type="hidden" name="status" value="in_progress">
        <button class="btn btn-primary w-100">Start work</button>
      </form>
    @endif

    @if($canComplete)
      <form method="POST" action="{{ route('maintenance.transition', $req) }}" class="form-stack">@csrf
        <input type="hidden" name="status" value="completed">
        <div>
          <label class="form-label" for="work_summary">What was done?</label>
          <textarea id="work_summary" name="work_summary" class="form-control" rows="3" required placeholder="Summarise the work completed">{{ old('work_summary') }}</textarea>
        </div>
        <div>
          <label class="form-label" for="completion_notes">Notes <span class="muted fw-normal">(optional)</span></label>
          <textarea id="completion_notes" name="completion_notes" class="form-control" rows="2" placeholder="Parts used, follow-ups, anything to remember">{{ old('completion_notes') }}</textarea>
        </div>
        <button class="btn btn-primary w-100">Mark as completed</button>
      </form>
    @endif

    @if(!$forward)
      @if($req->status === 'open')
        <p class="muted mb-0">Waiting for an administrator to assign a technician.</p>
      @elseif($req->status === 'assigned')
        <p class="muted mb-0">Waiting for {{ $req->technician?->name ?? 'the technician' }} to start work.</p>
      @elseif($req->status === 'in_progress')
        <p class="muted mb-0">{{ $req->technician?->name ?? 'The technician' }} is working on this.</p>
      @else
        <p class="muted mb-0">No further actions. This request is closed.</p>
      @endif
    @endif

    @if($canCancel)
      <form method="POST" action="{{ route('maintenance.transition', $req) }}" class="mt-3 pt-3" style="border-top: 1px solid var(--line);">@csrf
        <input type="hidden" name="status" value="cancelled">
        <button class="btn btn-danger-quiet btn-sm" onclick="return confirm('Cancel this request? This cannot be undone.')">Cancel request</button>
      </form>
    @endif
  </aside>
</div>
@endsection
