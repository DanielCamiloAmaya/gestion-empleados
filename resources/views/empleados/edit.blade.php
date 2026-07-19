@extends('layouts.app-master')
@section('title', 'Editar '.$empleado->full_name)
@section('eyebrow', 'Ciclo de vida')
@section('page-title', 'Editar perfil laboral')
@section('content')
    <div class="page-heading"><div><h2>{{ $empleado->full_name }}</h2><p>Actualiza la información vigente sin perder la trazabilidad del historial.</p></div></div>
    <form method="POST" action="{{ route('empleados.update', $empleado) }}" class="form-shell">
        @csrf @method('PUT')
        <div class="form-card">@include('empleados._form')<div class="form-actions"><a class="button button-secondary" href="{{ route('empleados.show', $empleado) }}">Cancelar</a><button class="button button-primary" type="submit">Guardar cambios</button></div></div>
        <aside class="aside-card"><h3>Control de cambios</h3><p>Los valores anteriores y nuevos se registrarán junto con el administrador, la fecha, IP y dispositivo de origen.</p></aside>
    </form>
@endsection
