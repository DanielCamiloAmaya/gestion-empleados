@extends('layouts.auth-master')

@section('title', 'Acceso de Recursos Humanos')

@section('content')
    <span class="auth-kicker">Acceso administrativo</span>
    <h2>Gestiona el talento</h2>
    <p class="auth-intro">Área restringida para líderes autorizados de Recursos Humanos.</p>

    <form action="{{ route('admin.login') }}" method="POST" class="auth-form">
        @csrf
        <div class="field">
            <label for="workspace">Espacio de trabajo</label>
            <input class="input" id="workspace" type="text" name="workspace" value="{{ old('workspace', config('app.default_organization')) }}" autocomplete="organization" required placeholder="mi-empresa">
        </div>
        <div class="field">
            <label for="name">Nombre o correo corporativo</label>
            <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="username" required autofocus placeholder="rrhh@empresa.com">
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input class="input" id="password" type="password" name="password" autocomplete="current-password" required placeholder="••••••••••••">
            <a class="text-link" href="{{ route('recovery.request', ['actor' => 'admin', 'workspace' => old('workspace', config('app.default_organization'))]) }}">¿Olvidaste tu contraseña?</a>
        </div>
        <p class="session-policy">Por seguridad, la sesión de RR. HH. expira tras 15 minutos de inactividad y al cerrar el navegador.</p>
        <button type="submit" class="button button-primary">Acceder al panel <span>→</span></button>
    </form>

    <p class="auth-switch">¿Eres colaborador? <a href="{{ route('login') }}">Volver al portal del empleado</a></p>
@endsection
