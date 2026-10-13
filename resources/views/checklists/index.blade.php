@extends('layouts.app')
@section('title', 'Checklists')
@section('content')
<x-page-header title="Checklist templates" sub="Reusable question sets. Starting an audit copies a template's questions into it.">
  <x-slot:actions><a class="btn btn-primary" href="{{ route('checklists.create') }}"><x-icon name="plus" :size="16" />New template</a></x-slot:actions>
</x-page-header>

@if($templates->isEmpty())
  <div class="surface">
    <x-empty title="No templates yet" hint="Write the questions you ask every time, such as &quot;Is the firewall enabled?&quot;, and reuse them across audits.">
      <a class="btn btn-primary" href="{{ route('checklists.create') }}">New template</a>
    </x-empty>
  </div>
@else
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Template</th><th>Questions</th></tr></thead>
      <tbody>
      @foreach($templates as $t)
        <tr>
          <td><span class="cell-title">{{ $t->name }}</span>@if($t->description)<span class="cell-sub">{{ $t->description }}</span>@endif</td>
          <td class="num">{{ $t->items_count }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection
