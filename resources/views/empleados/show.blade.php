@extends('layouts.app-master')
@section('title', $empleado->full_name)
@section('eyebrow', 'Directorio')
@section('page-title', 'Perfil laboral')
@section('content')
    <section class="profile-hero">
        <span class="avatar avatar-large">{{ $empleado->initials }}</span>
        <div><span class="badge badge-{{ $empleado->employment_status }}">{{ __('status.'.$empleado->employment_status) }}</span><h2>{{ $empleado->full_name }}</h2><p>{{ $empleado->job_title ?: 'Cargo por definir' }} · {{ $empleado->departamento?->nombre ?? 'Sin departamento' }}</p></div>
        @if(auth('admin')->check())<div class="actions"><a class="button button-secondary" href="{{ route('empleados.edit', $empleado) }}">Editar</a><form method="POST" action="{{ route('empleados.destroy', $empleado) }}" data-confirm="¿Archivar a esta persona? Su acceso será deshabilitado y el historial se conservará.">@csrf @method('DELETE')<button class="button button-danger" type="submit">Archivar</button></form></div>@endif
    </section>
    <div class="profile-grid">
        <section class="panel"><header class="panel-header"><div><h3>Posición actual</h3><p>Información organizacional</p></div></header><div class="panel-body"><dl class="definition-grid"><div><dt>Código</dt><dd>{{ $empleado->employee_code ?: 'Pendiente' }}</dd></div><div><dt>Departamento</dt><dd>{{ $empleado->departamento?->nombre ?? 'Sin asignar' }}</dd></div><div><dt>Jefe directo</dt><dd>{{ $empleado->manager?->full_name ?? 'No asignado' }}</dd></div><div><dt>Vinculación</dt><dd>{{ __('employment.'.$empleado->employment_type) }}</dd></div><div><dt>Ingreso</dt><dd>{{ $empleado->hire_date?->translatedFormat('d M Y') ?? 'Pendiente' }}</dd></div><div><dt>Sede</dt><dd>{{ $empleado->location ?: 'No registrada' }}</dd></div></dl></div></section>
        <section class="panel"><header class="panel-header"><div><h3>{{ auth('admin')->check() ? 'Contacto y acceso' : 'Equipo' }}</h3><p>{{ auth('admin')->check() ? 'Información restringida a RR. HH.' : 'Relaciones de trabajo' }}</p></div></header><div class="panel-body">@if(auth('admin')->check())<dl class="definition-grid"><div><dt>Correo</dt><dd>{{ $empleado->email }}</dd></div><div><dt>Usuario</dt><dd>{{ $empleado->username }}</dd></div><div><dt>Teléfono</dt><dd>{{ $empleado->phone ?: 'No registrado' }}</dd></div><div><dt>Reportes directos</dt><dd>{{ $empleado->directReports->count() }}</dd></div></dl>@else<p class="subtle">{{ $empleado->full_name }} trabaja en {{ $empleado->departamento?->nombre ?? 'la organización' }} como {{ $empleado->job_title ?: 'miembro del equipo' }}.</p>@endif</div></section>
    </div>
@endsection
