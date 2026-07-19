@extends('layouts.app-master')
@section('title', 'Solicitudes de ausencia')
@section('eyebrow', 'Tiempo y bienestar')
@section('page-title', 'Solicitudes de ausencia')
@section('content')
    <div class="page-heading reveal-item">
        <div><h2>{{ auth('admin')->check() ? 'Bandeja de aprobaciones' : 'Mi tiempo fuera' }}</h2><p>{{ auth('admin')->check() ? 'Revisa solicitudes, fechas y contexto antes de tomar una decisión.' : 'Solicita vacaciones, licencias o ausencias y sigue su estado.' }}</p></div>
        @if(!auth('admin')->check())<a class="button button-primary" href="{{ route('leave.create') }}">＋ Nueva solicitud</a>@endif
    </div>

    <section class="ops-strip reveal-item" aria-label="Resumen de solicitudes">
        <div><span>Pendientes</span><strong>{{ $metrics['pending'] }}</strong></div>
        <div><span>Aprobadas</span><strong>{{ $metrics['approved'] }}</strong></div>
        <div><span>Días aprobados este año</span><strong>{{ $metrics['days'] }}</strong></div>
    </section>

    <form class="filter-bar filter-bar-compact reveal-item" method="GET">
        <select class="select" name="status" aria-label="Filtrar por estado"><option value="">Todos los estados</option>@foreach(['pending','approved','rejected'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __('ops.request_status.'.$status) }}</option>@endforeach</select>
        <button class="button button-secondary" type="submit">Filtrar</button>
    </form>

    <section class="panel reveal-item"><div class="table-wrap"><table class="data-table"><thead><tr>@if(auth('admin')->check())<th>Persona</th>@endif<th>Tipo</th><th>Fechas</th><th>Días</th><th>Estado</th><th>Detalle</th>@if(auth('admin')->check())<th>Decisión</th>@endif</tr></thead><tbody>
        @forelse($requests as $leave)
            <tr>
                @if(auth('admin')->check())<td><span class="person"><span class="avatar">{{ $leave->employee->initials }}</span><span><strong>{{ $leave->employee->full_name }}</strong><small>{{ $leave->employee->departamento?->nombre }}</small></span></span></td>@endif
                <td>{{ __('ops.leave_types.'.$leave->type) }}</td>
                <td><strong>{{ $leave->start_date->format('d M') }}</strong><span class="subtle"> → {{ $leave->end_date->format('d M Y') }}</span></td>
                <td>{{ $leave->days }}</td>
                <td><span class="status-chip status-{{ $leave->status }}">{{ __('ops.request_status.'.$leave->status) }}</span></td>
                <td><span class="reason-text">{{ $leave->reason }}</span>@if($leave->review_note)<small class="review-note">RR. HH.: {{ $leave->review_note }}</small>@endif</td>
                @if(auth('admin')->check())<td>@if($leave->status === 'pending')<div class="decision-stack"><form method="POST" action="{{ route('leave.review', $leave) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="button button-primary button-small" type="submit">Aprobar</button></form><form method="POST" action="{{ route('leave.review', $leave) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><input class="input input-compact" name="review_note" required placeholder="Motivo del rechazo"><button class="button button-danger button-small" type="submit">Rechazar</button></form></div>@else<span class="subtle">{{ $leave->reviewer?->name ?? 'Procesada' }}</span>@endif</td>@endif
            </tr>
        @empty<tr><td colspan="7"><div class="empty-state"><strong>No hay solicitudes</strong>{{ auth('admin')->check() ? 'La bandeja está al día.' : 'Crea una solicitud cuando necesites tiempo fuera.' }}</div></td></tr>@endforelse
    </tbody></table></div>@include('layouts.partials.pagination', ['paginator' => $requests])</section>
@endsection
