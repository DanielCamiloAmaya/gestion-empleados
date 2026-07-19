@extends('layouts.auth-master')
@section('title', 'Nueva contraseña')
@section('content')
    <span class="auth-kicker">Enlace verificado</span>
    <h2>Crea una nueva contraseña</h2>
    <p class="auth-intro">Al guardar, PeopleOS cerrará las sesiones anteriores y revocará las credenciales asociadas.</p>
    <form method="POST" action="{{ route('recovery.update', ['actor' => $actor, 'token' => $token, 'workspace' => $recovery->organization->slug]) }}" class="auth-form">
        @csrf
        <div class="field"><label for="password">Nueva contraseña</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" required></div>
        <div class="field"><label for="password_confirmation">Confirmar contraseña</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
        <button class="button button-primary">Actualizar y cerrar otras sesiones</button>
    </form>
@endsection
