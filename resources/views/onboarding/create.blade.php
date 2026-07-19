@extends('layouts.app-master')
@section('title', 'Asignar tarea')
@section('eyebrow', 'Experiencia del empleado')
@section('page-title', 'Asignar tarea de onboarding')
@section('content')
    <div class="page-heading reveal-item"><div><h2>Una tarea, un responsable</h2><p>Define una acción concreta, su prioridad y la fecha en la que debe estar lista.</p></div></div>
    <form class="form-shell reveal-item" method="POST" action="{{ route('onboarding.store') }}">@csrf<div class="form-card"><div class="form-section"><h3>Nueva tarea</h3><div class="form-grid">
        <div class="field field-full"><label for="user_id">Persona</label><select class="select" id="user_id" name="user_id" required><option value="">Selecciona una persona</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('user_id')===(string)$employee->id)>{{ $employee->full_name }} · {{ $employee->job_title }}</option>@endforeach</select></div>
        <div class="field field-full"><label for="title">Tarea</label><input class="input" id="title" name="title" value="{{ old('title') }}" required maxlength="180" placeholder="Completar inducción de seguridad"></div>
        <div class="field field-full"><label for="description">Instrucciones</label><textarea class="textarea" id="description" name="description" maxlength="1500">{{ old('description') }}</textarea></div>
        <div class="field"><label for="due_date">Fecha límite</label><input class="input" id="due_date" type="date" name="due_date" min="{{ today()->format('Y-m-d') }}" value="{{ old('due_date') }}"></div>
        <div class="field"><label for="priority">Prioridad</label><select class="select" id="priority" name="priority" required>@foreach(['low','medium','high'] as $priority)<option value="{{ $priority }}" @selected(old('priority','medium')===$priority)>{{ __('ops.priority.'.$priority) }}</option>@endforeach</select></div>
    </div></div><div class="form-actions"><a class="button button-secondary" href="{{ route('onboarding.index') }}">Cancelar</a><button class="button button-primary">Asignar tarea</button></div></div><aside class="aside-card"><h3>Diseña acciones claras</h3><p>Una tarea debe poder completarse y verificarse. Evita usarla como recordatorio ambiguo.</p></aside></form>
@endsection
