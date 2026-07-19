@extends('control-center.layouts.auth')
@section('title', 'Cuenta activada')
@section('content')
<span class="cc-overline">Propietario verificado · {{ $invitation->organization->name }}</span>
<h2>Tu cuenta ya está bajo tu control.</h2>
<p class="cc-auth-intro">PeopleOS notificará la activación del workspace cuando finalicen las verificaciones legales y de dominio. Después podrás iniciar sesión y configurar MFA.</p>
<div class="cc-invitation-facts"><span><small>Workspace</small><strong>{{ $invitation->organization->slug }}</strong></span><span><small>Estado</small><strong>{{ $invitation->organization->lifecycle_status === 'active' ? 'Activo' : 'Verificación empresarial en curso' }}</strong></span></div>
@if($invitation->organization->is_active)<a class="cc-button cc-button-primary" href="{{ route('admin.login',['workspace'=>$invitation->organization->slug]) }}">Iniciar sesión como propietario</a>@else<p class="cc-auth-note">No necesitas realizar ninguna otra acción por ahora.</p>@endif
@endsection
