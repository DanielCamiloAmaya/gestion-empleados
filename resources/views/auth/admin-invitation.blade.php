@extends('layouts.auth-master')
@section('title', 'Activar administración')
@section('content')
    <span class="auth-kicker">Invitación administrativa protegida</span>
    <h2>Activa tu acceso, {{ $invitation->name }}</h2>
    <p class="auth-intro">Administrarás {{ $invitation->organization->name }} con el rol {{ $invitation->role?->name }}. El enlace funciona una vez y vence {{ $invitation->expires_at->diffForHumans() }}.</p>
    <form method="POST" action="{{ route('admin-invitations.accept', ['token' => $token, 'workspace' => $invitation->organization->slug]) }}" class="auth-form">
        @csrf
        <div class="field"><label for="password">Nueva contraseña</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" required></div>
        <div class="field"><label for="password_confirmation">Confirmar contraseña</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
        <button class="button button-primary" type="submit">Activar acceso administrativo</button>
    </form>
@endsection
