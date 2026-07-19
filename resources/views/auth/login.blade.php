@extends('layouts.auth-master')

@section('title', 'Portal del empleado')

@section('content')
    <span class="auth-kicker">Bienvenido de nuevo</span>
    <h2>Tu espacio de trabajo</h2>
    <p class="auth-intro">Consulta tu perfil, equipo y directorio corporativo en un entorno protegido.</p>

    <form action="{{ route('login') }}" method="POST" class="auth-form">
        @csrf
        <div class="field">
            <label for="workspace">Espacio de trabajo</label>
            <input class="input" id="workspace" type="text" name="workspace" value="{{ old('workspace', config('app.default_organization')) }}" autocomplete="organization" required placeholder="mi-empresa">
        </div>
        <div class="field">
            <label for="username">Usuario o correo corporativo</label>
            <input class="input" id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus placeholder="nombre@empresa.com">
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input class="input" id="password" type="password" name="password" autocomplete="current-password" required placeholder="••••••••••••">
            <a class="text-link" href="{{ route('recovery.request', ['actor' => 'employee', 'workspace' => old('workspace', config('app.default_organization'))]) }}">¿Olvidaste tu contraseña?</a>
        </div>
        <p class="session-policy">Por seguridad, la sesión expira tras 30 minutos de inactividad y al cerrar el navegador.</p>
        <button type="submit" class="button button-primary">Entrar a PeopleOS <span>→</span></button>
    </form>

    <p class="auth-switch">¿Eres parte de Recursos Humanos? <a href="{{ route('admin.login') }}">Acceso administrativo</a></p>
@endsection
