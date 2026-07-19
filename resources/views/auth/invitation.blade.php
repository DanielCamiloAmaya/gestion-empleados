@extends('layouts.auth-master')
@section('title', 'Activar cuenta')
@section('content')
    <span class="auth-kicker">Invitación protegida</span><h2>Crea tu acceso, {{ $invitation->employee->first_name }}</h2><p class="auth-intro">Estás activando tu cuenta en {{ $invitation->organization->name }}. Este enlace vence {{ $invitation->expires_at->diffForHumans() }} y funciona una sola vez.</p>
    <form method="POST" action="{{ route('invitations.store', ['token' => $token, 'workspace' => $invitation->organization->slug]) }}" class="auth-form">@csrf<div class="field"><label for="password">Nueva contraseña</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" required><small>Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</small></div><div class="field"><label for="password_confirmation">Confirmar contraseña</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div><button class="button button-primary" type="submit">Activar mi cuenta <span>→</span></button></form>
@endsection
