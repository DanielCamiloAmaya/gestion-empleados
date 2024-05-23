@extends('layouts.app-master')

@section('content')
<div class="container mt-5">
    <h1>Dashboard de Administrador</h1>

    @auth('admin')
        <p>Bienvenido {{ auth()->guard('admin')->user()->name }}, estás autenticado en el dashboard de administrador</p>
        <p>
            <a href="{{ route('departamentos.index') }}" class="btn btn-primary">Gestionar Departamentos</a>
            <a href="{{ route('empleados.index') }}" class="btn btn-primary">Gestionar Empleados</a>
        </p>
        <p>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </p>
    @endauth
</div>
@endsection






