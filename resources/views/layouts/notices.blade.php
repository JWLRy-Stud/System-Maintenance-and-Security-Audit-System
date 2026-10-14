@if(session('ok'))
  <div class="notice notice-ok" role="status"><x-icon name="check" />{{ session('ok') }}</div>
@endif
@if(session('warn'))
  <div class="notice notice-err" role="alert"><x-icon name="alert" /><span>{{ session('warn') }}</span></div>
@endif
@if($errors->any())
  <div class="notice notice-err" role="alert"><x-icon name="alert" />
    @if($errors->count() === 1)
      <span>{{ $errors->first() }}</span>
    @else
      <div><strong>Please fix the following:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif
  </div>
@endif
