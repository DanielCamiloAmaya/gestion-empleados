@extends('layouts.app-master')
@section('title', 'Nuevo departamento')
@section('eyebrow', 'Diseño organizacional')
@section('page-title', 'Nuevo departamento')
@section('content')
    <div class="page-heading"><div><h2>Crear un área</h2><p>Define una unidad organizacional y su centro de responsabilidad.</p></div></div>
    <form method="POST" action="{{ route('departamentos.store') }}" class="form-shell">@csrf<div class="form-card">@include('departamentos._form')<div class="form-actions"><a class="button button-secondary" href="{{ route('departamentos.index') }}">Cancelar</a><button class="button button-primary" type="submit">Crear departamento</button></div></div><aside class="aside-card"><h3>Buena estructura</h3><ul class="check-list"><li>Evita nombres ambiguos o duplicados.</li><li>Asocia un centro de costo cuando aplique.</li><li>Describe el propósito, no solo las tareas.</li></ul></aside></form>
@endsection
