@extends('layouts.app-master')
@section('title', 'Crear objetivo')
@section('eyebrow', 'Desempeño y crecimiento')
@section('page-title', 'Crear objetivo')
@section('content')
    <div class="page-heading reveal-item"><div><h2>Define el resultado esperado</h2><p>Un objetivo útil explica qué cambia y cuándo debe lograrse.</p></div></div>
    <form class="form-shell reveal-item" method="POST" action="{{ route('goals.store') }}">@csrf<div class="form-card"><div class="form-section"><h3>Nuevo objetivo</h3><div class="form-grid">
        <div class="field field-full"><label for="user_id">Persona</label><select class="select" id="user_id" name="user_id" required><option value="">Selecciona una persona</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('user_id')===(string)$employee->id)>{{ $employee->full_name }} · {{ $employee->job_title }}</option>@endforeach</select></div>
        <div class="field field-full"><label for="title">Resultado esperado</label><input class="input" id="title" name="title" value="{{ old('title') }}" required maxlength="180" placeholder="Reducir el tiempo de respuesta al cliente"></div>
        <div class="field field-full"><label for="description">Cómo se medirá</label><textarea class="textarea" id="description" name="description" maxlength="1500">{{ old('description') }}</textarea></div>
        <div class="field field-full"><label for="target_date">Fecha objetivo</label><input class="input" id="target_date" type="date" name="target_date" min="{{ today()->format('Y-m-d') }}" value="{{ old('target_date') }}"></div>
    </div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('goals.index') }}">Cancelar</a><button class="button button-primary">Activar objetivo</button></div></div><aside class="aside-card"><h3>Objetivos que funcionan</h3><ul class="check-list"><li>Describe un resultado observable.</li><li>Explica cómo se medirá.</li><li>Define una fecha realista.</li></ul></aside></form>
@endsection
