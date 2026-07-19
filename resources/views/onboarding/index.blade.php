@extends('layouts.app-master')
@section('title', 'Onboarding')
@section('eyebrow', 'Experiencia del empleado')
@section('page-title', 'Onboarding')

@section('content')
    <div class="page-heading reveal-item">
        <div>
            <h2>{{ auth('admin')->check() ? 'Activación de nuevas personas' : 'Mis tareas y mi equipo' }}</h2>
            <p>Gestiona tareas con entregables verificables, revisiones claras y trazabilidad por versión.</p>
        </div>
        @if(auth('admin')->check())
            <a class="button button-primary" href="{{ route('onboarding.create') }}">＋ Asignar tarea</a>
        @endif
    </div>

    <form class="filter-bar filter-bar-compact reveal-item" method="GET">
        <select class="select" name="status" aria-label="Filtrar tareas por estado">
            <option value="">Todos los estados</option>
            @foreach(['pending','in_progress','completed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('ops.task_status.'.$status) }}</option>
            @endforeach
        </select>
        <button class="button button-secondary">Filtrar</button>
    </form>

    <section class="task-board reveal-item">
        @forelse($tasks as $task)
            <article class="task-card priority-{{ $task->priority }}">
                <div class="task-top">
                    <span class="status-chip status-{{ $task->status }}">{{ __('ops.task_status.'.$task->status) }}</span>
                    <span class="priority-label">{{ __('ops.priority.'.$task->priority) }}</span>
                </div>

                @if(auth('admin')->check() || $task->user_id !== auth()->id())
                    <span class="person compact-person">
                        <span class="avatar">{{ $task->employee->initials }}</span>
                        <span><strong>{{ $task->employee->full_name }}</strong><small>{{ $task->employee->job_title }}</small></span>
                    </span>
                @endif

                <h3>{{ $task->title }}</h3>
                <p>{{ $task->description ?: 'Sin instrucciones adicionales.' }}</p>

                <div class="task-due">
                    <span>Fecha límite</span>
                    <strong @class(['is-overdue' => $task->due_date?->isPast() && $task->status !== 'completed'])>{{ $task->due_date?->translatedFormat('d M Y') ?? 'Sin fecha' }}</strong>
                </div>

                <div class="delivery-summary">
                    @if($task->latestSubmission)
                        <span class="status-chip submission-{{ $task->latestSubmission->status }}">{{ __('ops.submission_status.'.$task->latestSubmission->status) }}</span>
                        <span>Versión {{ $task->latestSubmission->version }} · {{ $task->latestSubmission->files->count() }} archivo(s)</span>
                    @else
                        <span class="submission-empty-dot"></span><span>Sin entregables todavía</span>
                    @endif
                </div>

                <a class="button button-secondary task-open" href="{{ route('onboarding.show', $task) }}">
                    {{ $task->user_id === auth()->id() ? 'Abrir tarea y entregar' : 'Revisar entregables' }} →
                </a>
            </article>
        @empty
            <div class="panel empty-state"><strong>No hay tareas</strong>{{ auth('admin')->check() ? 'Asigna la primera tarea de incorporación.' : 'No tienes tareas propias ni entregas de tu equipo por revisar.' }}</div>
        @endforelse
    </section>

    @include('layouts.partials.pagination', ['paginator' => $tasks])
@endsection
