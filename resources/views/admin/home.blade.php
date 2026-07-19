@extends('layouts.app-master')

@section('title', 'Resumen ejecutivo')
@section('eyebrow', 'Centro de mando')
@section('page-title', 'Resumen de personas')

@section('content')
    <div class="page-heading reveal-item">
        <div>
            <h2>Hola, {{ Str::before(auth('admin')->user()->name, ' ') }}.</h2>
            <p>Esta es la fotografía actual de tu organización y los movimientos recientes del equipo.</p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="{{ route('departamentos.create') }}">Nuevo departamento</a>
            <a class="button button-primary" href="{{ route('empleados.create') }}"><span>＋</span> Incorporar persona</a>
        </div>
    </div>

    <section class="people-pulse reveal-item" aria-label="Pulso operativo de personas">
        <div class="pulse-orbit" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="pulse-copy">
            <span class="pulse-kicker">People Pulse · hoy</span>
            <h3>Lo que necesita atención</h3>
            <p>Una vista viva de solicitudes, tareas y objetivos para actuar antes de que se conviertan en bloqueos.</p>
        </div>
        <div class="pulse-signals">
            <a href="{{ route('leave.index', ['status' => 'pending']) }}"><strong>{{ $operations['pendingLeave'] }}</strong><span>solicitudes pendientes</span></a>
            <a href="{{ route('onboarding.index') }}"><strong>{{ $operations['overdueTasks'] }}</strong><span>tareas vencidas</span></a>
            <a href="{{ route('goals.index') }}"><strong>{{ $operations['activeGoals'] }}</strong><span>objetivos activos</span></a>
        </div>
    </section>

    <section class="metrics-grid reveal-item" aria-label="Indicadores de talento">
        <article class="metric-card">
            <span class="metric-label">Headcount total</span>
            <strong class="metric-value">{{ number_format($metrics['headcount']) }}</strong>
            <span class="metric-note metric-note-positive">{{ $metrics['active'] }} personas activas</span>
        </article>
        <article class="metric-card metric-blue">
            <span class="metric-label">En incorporación</span>
            <strong class="metric-value">{{ number_format($metrics['onboarding']) }}</strong>
            <span class="metric-note">Procesos de onboarding abiertos</span>
        </article>
        <article class="metric-card metric-amber">
            <span class="metric-label">Ausencias activas</span>
            <strong class="metric-value">{{ number_format($metrics['leave']) }}</strong>
            <span class="metric-note">Personas temporalmente ausentes</span>
        </article>
        <article class="metric-card metric-purple">
            <span class="metric-label">Departamentos</span>
            <strong class="metric-value">{{ number_format($metrics['departments']) }}</strong>
            <span class="metric-note">{{ $activeRate }}% del headcount activo</span>
        </article>
    </section>

    <div class="dashboard-grid reveal-item">
        <section class="panel">
            <header class="panel-header">
                <div><h3>Incorporaciones recientes</h3><p>Últimas personas agregadas a PeopleOS</p></div>
                <a class="text-link" href="{{ route('empleados.index') }}">Ver directorio →</a>
            </header>
            <div class="panel-body employee-list">
                @forelse($recentEmployees as $employee)
                    <a class="employee-row" href="{{ route('empleados.show', $employee) }}">
                        <span class="person">
                            <span class="avatar">{{ $employee->initials }}</span>
                            <span><strong>{{ $employee->full_name }}</strong><small>{{ $employee->job_title ?: 'Cargo por definir' }}</small></span>
                        </span>
                        <span class="subtle">{{ $employee->departamento?->nombre ?? 'Sin departamento' }}</span>
                        <span class="badge badge-{{ $employee->employment_status }}">{{ __('status.'.$employee->employment_status) }}</span>
                    </a>
                @empty
                    <div class="empty-state"><strong>Aún no hay personas</strong>Incorpora el primer talento de tu organización.</div>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <header class="panel-header"><div><h3>Distribución del equipo</h3><p>Personas activas por área</p></div></header>
            <div class="panel-body department-bars">
                @forelse($departmentStats as $department)
                    <div class="department-bar">
                        <div class="bar-label"><span>{{ $department->nombre }}</span><strong>{{ $department->empleados_count }}</strong></div>
                        <progress class="progress" max="{{ max($metrics['active'], 1) }}" value="{{ $department->empleados_count }}">{{ $department->empleados_count }}</progress>
                    </div>
                @empty
                    <div class="empty-state"><strong>Sin estructura</strong>Crea departamentos para visualizar la distribución.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="panel dashboard-audit reveal-item">
        <header class="panel-header">
            <div><h3>Actividad reciente</h3><p>Cambios administrativos registrados</p></div>
            <a class="text-link" href="{{ route('audit.index') }}">Abrir auditoría →</a>
        </header>
        <div class="panel-body activity-list">
            @forelse($recentActivity as $activity)
                <div class="activity-item">
                    <span class="activity-icon">✓</span>
                    <div><p><strong>{{ $activity->actor_name }}</strong> · {{ __('events.'.$activity->event) }}</p><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time></div>
                </div>
            @empty
                <div class="empty-state"><strong>Sin movimientos registrados</strong>Los cambios futuros aparecerán aquí.</div>
            @endforelse
        </div>
    </section>
@endsection
