@extends('layouts.app-master')
@section('title', 'Incorporar persona')
@section('eyebrow', 'Ciclo de vida')
@section('page-title', 'Incorporar persona')
@section('content')
    <div class="page-heading"><div><h2>Nueva incorporación</h2><p>Crea la ficha laboral y el acceso protegido de una persona.</p></div></div>
    <form method="POST" action="{{ route('empleados.store') }}" class="form-shell">
        @csrf
        <div class="form-card">@include('empleados._form')<div class="form-actions"><a class="button button-secondary" href="{{ route('empleados.index') }}">Cancelar</a><button class="button button-primary" type="submit">Crear empleado</button></div></div>
        <aside class="aside-card"><h3>Incorporación segura</h3><ul class="check-list"><li>Valida que el correo sea corporativo.</li><li>Asigna cargo, área y jefe directo.</li><li>Usa una contraseña temporal única.</li><li>El cambio quedará registrado en auditoría.</li></ul></aside>
    </form>
@endsection
