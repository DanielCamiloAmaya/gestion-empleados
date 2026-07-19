@extends('layouts.app-master')

@section('title', 'Mi espacio')
@section('eyebrow', 'Portal del empleado')
@section('page-title', 'Mi espacio')

@section('content')
    <div class="page-heading reveal-item">
        <div><h2>Hola, {{ $employee->first_name }}.</h2><p>Aquí tienes tu información laboral y los accesos principales de tu organización.</p></div>
        <a class="button button-primary" href="{{ route('leave.create') }}">＋ Solicitar ausencia</a>
    </div>

    <section class="profile-hero reveal-item">
        <span class="avatar avatar-large">{{ $employee->initials }}</span>
        <div>
            <span class="badge badge-{{ $employee->employment_status }}">{{ __('status.'.$employee->employment_status) }}</span>
            <h2>{{ $employee->full_name }}</h2>
            <p>{{ $employee->job_title ?: 'Cargo por definir' }} · {{ $employee->departamento?->nombre ?? 'Sin departamento' }}</p>
        </div>
    </section>

    <section class="employee-operations reveal-item" aria-label="Mi operación">
        <a class="operation-tile operation-signal" href="{{ route('onboarding.index') }}">
            <span class="operation-icon">↗</span>
            <span><small>Onboarding</small><strong>{{ $employee->onboardingTasks->count() }} tareas abiertas</strong></span>
            <span>→</span>
        </a>
        <a class="operation-tile" href="{{ route('goals.index') }}">
            <span class="operation-icon">⌾</span>
            <span><small>Desempeño</small><strong>{{ $employee->performanceGoals->count() }} objetivos activos</strong></span>
            <span>→</span>
        </a>
        <a class="operation-tile" href="{{ route('leave.index') }}">
            <span class="operation-icon">◌</span>
            <span><small>Tiempo libre</small><strong>{{ $employee->leaveRequests->where('status', 'pending')->count() }} solicitudes pendientes</strong></span>
            <span>→</span>
        </a>
    </section>

    <div class="profile-grid reveal-item">
        <section class="panel">
            <header class="panel-header"><div><h3>Información laboral</h3><p>Tu ficha vigente en PeopleOS</p></div></header>
            <div class="panel-body">
                <dl class="definition-grid">
                    <div><dt>Código</dt><dd>{{ $employee->employee_code ?: 'Pendiente' }}</dd></div>
                    <div><dt>Fecha de ingreso</dt><dd>{{ $employee->hire_date?->translatedFormat('d M Y') ?? 'Pendiente' }}</dd></div>
                    <div><dt>Modalidad</dt><dd>{{ __('employment.'.$employee->employment_type) }}</dd></div>
                    <div><dt>Sede</dt><dd>{{ $employee->location ?: 'No registrada' }}</dd></div>
                    <div><dt>Jefe directo</dt><dd>{{ $employee->manager?->full_name ?? 'No asignado' }}</dd></div>
                    <div><dt>Equipo a cargo</dt><dd>{{ $employee->directReports->count() }} personas</dd></div>
                </dl>
            </div>
        </section>
        <aside class="panel">
            <header class="panel-header"><div><h3>Accesos rápidos</h3><p>Conecta con tu organización</p></div></header>
            <div class="panel-body employee-list">
                <a class="employee-row" href="{{ route('empleados.index') }}"><span class="person"><span class="avatar">◎</span><span><strong>Directorio</strong><small>Encuentra personas y equipos</small></span></span><span>→</span></a>
                <a class="employee-row" href="{{ route('departamentos.index') }}"><span class="person"><span class="avatar">▦</span><span><strong>Organización</strong><small>Conoce todas las áreas</small></span></span><span>→</span></a>
                <a class="employee-row" href="{{ route('goals.index') }}"><span class="person"><span class="avatar">⌾</span><span><strong>Mis objetivos</strong><small>Actualiza tu progreso</small></span></span><span>→</span></a>
            </div>
        </aside>
    </div>
@endsection
