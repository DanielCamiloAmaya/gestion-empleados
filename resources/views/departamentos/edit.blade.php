@extends('layouts.app-master')

@section('content')
    <div class="container mt-5">
        <h1>Editar Departamento</h1>

        <form method="POST" action="{{ route('departamentos.update', $departamento) }}">
            @csrf
            @method('PUT')

            <div class="form-group mt-5">
                <label for="nombre">Nombre del Departamento</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ $departamento->nombre }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Departamento</button>
        </form>
    </div>
@endsection
