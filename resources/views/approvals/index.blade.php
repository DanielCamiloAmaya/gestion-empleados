@extends('layouts.app-master')
@section('title', 'Bandeja de decisiones')
@section('eyebrow', 'Control operativo')
@section('page-title', 'Bandeja de decisiones')
@section('content')
    <section class="decision-hero reveal-item" aria-label="Estado de la bandeja">
        <div class="decision-count"><span>Por decidir</span><strong>{{ $total }}</strong></div>
        <div><h2>Cada decisión conserva su contexto</h2><p>Ausencias y entregables ordenados para actuar con rapidez, motivo y trazabilidad.</p></div>
        <div class="decision-split"><span><strong>{{ $leaves->count() }}</strong> ausencias</span><span><strong>{{ $submissions->count() }}</strong> entregables</span></div>
    </section>
    <div class="approval-layout reveal-item">
        <section class="panel">
            <header class="panel-header"><div><span class="eyebrow">Tiempo</span><h3>Solicitudes de ausencia</h3></div><a class="text-link" href="{{ route('leave.index') }}">Ver historial →</a></header>
            <div class="approval-list">
                @forelse($leaves as $leave)
                    <article class="approval-card">
                        <div class="approval-card-head"><span class="person"><span class="avatar">{{ $leave->employee->initials }}</span><span><strong>{{ $leave->employee->full_name }}</strong><small>{{ $leave->employee->departamento?->nombre }}</small></span></span><span class="status-chip status-pending">{{ $leave->days }} días</span></div>
                        <div class="approval-context"><strong>{{ __('ops.leave_types.'.$leave->type) }}</strong><span>{{ $leave->start_date->format('d M') }} → {{ $leave->end_date->format('d M Y') }}</span><p>{{ $leave->reason }}</p></div>
                        <div class="decision-actions"><form method="POST" action="{{ route('approvals.leave.review', $leave) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="button button-primary button-small" type="submit">Aprobar</button></form><form method="POST" action="{{ route('approvals.leave.review', $leave) }}" class="reject-form">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><input class="input input-compact" name="review_note" required placeholder="Motivo de la decisión"><button class="button button-danger button-small" type="submit">Rechazar</button></form></div>
                    </article>
                @empty<div class="empty-state"><strong>Sin ausencias pendientes</strong>La bandeja de tiempo está al día.</div>@endforelse
            </div>
        </section>
        <section class="panel">
            <header class="panel-header"><div><span class="eyebrow">Evidencia</span><h3>Entregables profesionales</h3></div><a class="text-link" href="{{ route('onboarding.index') }}">Ver tareas →</a></header>
            <div class="approval-list">
                @forelse($submissions as $submission)
                    <article class="approval-card">
                        <div class="approval-card-head"><span class="person"><span class="avatar">{{ $submission->task->employee->initials }}</span><span><strong>{{ $submission->task->employee->full_name }}</strong><small>{{ $submission->task->employee->departamento?->nombre }}</small></span></span><span class="status-chip status-pending">v{{ $submission->version }}</span></div>
                        <div class="approval-context"><strong>{{ $submission->task->title }}</strong><span>{{ $submission->files->count() }} archivo(s) · {{ $submission->submitted_at->diffForHumans() }}</span><p>{{ $submission->message ?: 'Sin mensaje adicional.' }}</p></div>
                        <a class="button button-secondary button-small" href="{{ route('onboarding.show', $submission->task) }}">Revisar evidencia →</a>
                    </article>
                @empty<div class="empty-state"><strong>Sin entregables pendientes</strong>No hay evidencia esperando revisión.</div>@endforelse
            </div>
        </section>
    </div>
@endsection
