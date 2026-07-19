@extends('control-center.layouts.app')
@section('title', 'Sesión de soporte '.$grant->ticket_reference)
@section('eyebrow', 'Acceso temporal · solo lectura')
@section('page-title', $organization->name)
@section('content')
<section class="cc-support-banner"><div><span class="cc-overline">Sesión JIT aprobada por el cliente</span><h2>{{ $grant->ticket_reference }}</h2><p>{{ $grant->reason }}</p></div><div><strong data-expiry="{{ $grant->expires_at->toIso8601String() }}">{{ $grant->expires_at->diffForHumans() }}</strong><small>Vencimiento automático</small></div></section>
<div class="cc-scope-strip">@foreach($grant->scopes as $scope)<span>{{ $scope }}</span>@endforeach</div>
<section class="cc-panel"><header><div><span class="cc-overline">Diagnóstico autorizado</span><h2>Metadatos operativos</h2><p>No se muestran expedientes, documentos, compensación ni información personal de empleados.</p></div></header>
<div class="cc-diagnostic-grid">
    <article><span>Workspace</span><strong>{{ $organization->slug }}</strong><small>{{ $organization->lifecycle_status }} · {{ $organization->country_code }}</small></article>
    <article><span>Suscripción</span><strong>{{ $organization->subscription?->plan?->name }}</strong><small>{{ $organization->subscription?->status }} · {{ $organization->seat_limit }} puestos</small></article>
    <article><span>Identidad</span><strong>{{ $organization->domains->where('verification_status','verified')->count() }} dominios</strong><small>{{ $organization->legalEntities->where('verification_status','verified')->count() }} entidades verificadas</small></article>
    <article><span>Volumen</span><strong>{{ $organization->employees_count }} personas</strong><small>{{ $organization->admins_count }} administradores</small></article>
</div></section>
<section class="cc-session-boundary"><strong>Límite de la sesión</strong><p>Este acceso no autentica al especialista como administrador del cliente. Todos los datos expuestos están enumerados en el alcance aprobado.</p><a class="cc-button cc-button-secondary" href="{{ route('control.organizations.show',$organization) }}">Cerrar diagnóstico</a></section>
@endsection
