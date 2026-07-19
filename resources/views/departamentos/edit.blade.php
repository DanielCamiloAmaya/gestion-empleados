@extends('layouts.app-master')
@section('title', 'Editar '.$departamento->nombre)
@section('eyebrow', 'Diseño organizacional')
@section('page-title', 'Editar departamento')
@section('content')
    <div class="page-heading"><div><h2>{{ $departamento->nombre }}</h2><p>Actualiza la definición del área manteniendo sus relaciones actuales.</p></div></div>
    <form method="POST" action="{{ route('departamentos.update', $departamento) }}" class="form-shell">@csrf @method('PUT')<div class="form-card">@include('departamentos._form')<div class="form-actions"><a class="button button-secondary" href="{{ route('departamentos.index') }}">Cancelar</a><button class="button button-primary" type="submit">Guardar cambios</button></div></div><aside class="aside-card"><h3>Impacto del cambio</h3><p>El nuevo nombre será visible inmediatamente en el directorio y en todos los perfiles asociados.</p></aside></form>
@endsection
