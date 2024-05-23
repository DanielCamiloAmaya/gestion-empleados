@extends('layouts.auth-master')

@section('content')
    <form action="/register" method="POST">
        @csrf
        <h1>Crear cuenta</h1>
        @include('layouts.partials.messages')
        <div class="form-floating mb-3">
            <input type="text" placeholder="first_name" name="first_name" class="form-control" id="firstnameInput" aria-describedby="firstnameHelp">
            <label for="firstnameInput" class="form-label">Nombre</label>
        </div>
        <div class="form-floating mb-3">
            <input type="text" placeholder="last_name" name="last_name" class="form-control" id="lastnameInput" aria-describedby="lastnameHelp">
            <label for="lastnameInput" class="form-label">Apellidos</label>
        </div>
        <div class="form-floating mb-3">
            <select name="departamento_id" class="form-control" id="departamentoInput" aria-describedby="departamentoHelp">
                <option value="">Selecciona un departamento</option>
                @foreach($departamentos as $departamento)
                    <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
                @endforeach
            </select>
            <label for="departamentoInput" class="form-label">Departamento</label>
        </div>
        <div class="form-floating mb-3">
            <input type="text" placeholder="name@example.com" name="email" class="form-control" id="emailInput" aria-describedby="emailHelp">
            <label for="emailInput" class="form-label">Dirección de correo electrónico</label>
            <div id="emailHelp" class="form-text">Nunca compartiremos su correo electrónico con nadie más.</div>
        </div>
        <div class="form-floating mb-3">
            <input type="text" placeholder="username" name="username" class="form-control" id="usernameInput">
            <label for="usernameInput" class="form-label">Username</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" placeholder="password" name="password" class="form-control" id="passwordInput">
            <label for="passwordInput" class="form-label">Contraseña</label>
            <div id="passwordHelp" class="form-text">La contraseña debe tener al menos 8 caracteres, incluyendo al menos</div>
        <div id="passwordHelp2" class="form-text">una letra minúscula, mayúscula, un número y un carácter especial.</div>
        </div>
        <div class="form-floating mb-3">
            <input type="password" placeholder="password_confirmation" name="password_confirmation" class="form-control" id="passwordConfirmationInput">
            <label for="passwordConfirmationInput" class="form-label">Confirmar contraseña</label>
        </div>
        <div class="mb-3">
            <a href="/login">Login</a>
        </div>
        <button type="submit" class="btn btn-primary">Crear cuenta</button>
    </form>
@endsection

