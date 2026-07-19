@extends('layouts.app-master')
@section('title', 'Objetivos')
@section('eyebrow', 'Desempeño y crecimiento')
@section('page-title', 'Objetivos')
@section('content')
    <div class="page-heading reveal-item"><div><h2>{{ auth('admin')->check() ? 'Objetivos del equipo' : 'Mi progreso' }}</h2><p>Alinea resultados concretos, fechas y avance visible.</p></div>@if(auth('admin')->check())<a class="button button-primary" href="{{ route('goals.create') }}">＋ Crear objetivo</a>@endif</div>
    <section class="goal-grid reveal-item">
        @forelse($goals as $goal)
            <article class="goal-card">
                <div class="goal-owner">@if(auth('admin')->check())<span class="person compact-person"><span class="avatar">{{ $goal->employee->initials }}</span><span><strong>{{ $goal->employee->full_name }}</strong><small>{{ $goal->employee->departamento?->nombre }}</small></span></span>@endif<span class="status-chip status-{{ $goal->status }}">{{ __('ops.goal_status.'.$goal->status) }}</span></div>
                <h3>{{ $goal->title }}</h3><p>{{ $goal->description ?: 'Sin descripción adicional.' }}</p>
                <div class="goal-progress-label"><span>Progreso</span><strong>{{ $goal->progress }}%</strong></div><progress class="progress goal-progress" max="100" value="{{ $goal->progress }}">{{ $goal->progress }}%</progress>
                <div class="task-due"><span>Fecha objetivo</span><strong>{{ $goal->target_date?->translatedFormat('d M Y') ?? 'Sin fecha' }}</strong></div>
                @if($goal->status !== 'completed')<form class="inline-progress" method="POST" action="{{ route('goals.progress', $goal) }}">@csrf @method('PATCH')<input class="input" type="number" name="progress" min="0" max="100" value="{{ $goal->progress }}" aria-label="Progreso de {{ $goal->title }}"><button class="button button-secondary button-small">Guardar progreso</button></form>@endif
            </article>
        @empty<div class="panel empty-state"><strong>No hay objetivos activos</strong>{{ auth('admin')->check() ? 'Crea el primer objetivo para alinear al equipo.' : 'RR. HH. aún no ha asignado objetivos.' }}</div>@endforelse
    </section>
    @include('layouts.partials.pagination', ['paginator' => $goals])
@endsection
