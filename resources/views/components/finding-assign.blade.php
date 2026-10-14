@props(['finding', 'users'])
<details class="assign">
  <summary class="btn btn-sm btn-outline-secondary">{{ $finding->assigned_to ? 'Reassign' : 'Assign' }}</summary>
  <form method="POST" action="{{ route('findings.assign', $finding) }}" class="assign-form">@csrf @method('PATCH')
    <label class="form-label" for="assign-{{ $finding->id }}">Who fixes it?</label>
    <select id="assign-{{ $finding->id }}" name="assigned_to" class="form-select form-select-sm">
      <option value="">Unassigned</option>
      @foreach($users as $u)<option value="{{ $u->id }}" @selected($finding->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach
    </select>
    <label class="form-label" for="due-{{ $finding->id }}">Due date</label>
    <input id="due-{{ $finding->id }}" type="date" name="due_date" class="form-control form-control-sm" value="{{ $finding->due_date?->toDateString() }}">
    <button class="btn btn-sm btn-primary">Save</button>
  </form>
</details>
