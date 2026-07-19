@extends('layouts.auth-master')
@section('title', 'Recuperar acceso')
@section('content')
    <span class="auth-kicker">Recuperación protegida</span>
    <h2>Recupera tu acceso</h2>
    <p class="auth-intro">Enviaremos un enlace de un solo uso si el correo pertenece a una cuenta {{ $actor === 'admin' ? 'administrativa' : 'de empleado' }} activa.</p>
    <form method="POST" action="{{ route('recovery.send', ['actor' => $actor, 'workspace' => request('workspace')]) }}" class="auth-form">
        @csrf
        <div class="field"><label for="email">Correo corporativo</label><input class="input" id="email" name="email" type="email" autocomplete="email" required></div>
        <button class="button button-primary">Enviar enlace seguro</button>
    </form>
    @if(session('recovery_url'))<p class="session-policy"><a href="{{ session('recovery_url') }}">Abrir enlace local de recuperación</a></p>@endif
@endsection
