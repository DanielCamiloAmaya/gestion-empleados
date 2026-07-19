@extends('control-center.layouts.app')
@section('title', 'Equipo interno')
@section('eyebrow', 'Identidad de plataforma')
@section('page-title', 'Equipo interno')
@section('content')
@if(session('platform_invitation_url'))<section class="cc-local-link"><strong>Enlace local de activación</strong><a href="{{ session('platform_invitation_url') }}">{{ session('platform_invitation_url') }}</a></section>@endif
<section class="cc-command-hero cc-users-hero"><div><span class="cc-overline">Cuentas individuales</span><h2>Nadie comparte una identidad privilegiada.</h2><p>Cada función tiene capacidades explícitas, MFA obligatorio y actividad atribuible.</p></div><div class="cc-integrity"><span class="is-ok"></span><strong>Mínimo privilegio</strong><small>{{ $users->where('status','active')->count() }} identidades activas</small></div></section>
<div class="cc-two-columns cc-align-start">
<section class="cc-panel"><header><div><span class="cc-overline">Invitación segura</span><h2>Agregar integrante</h2></div></header><form method="POST" action="{{ route('control.users.store') }}" class="cc-form">@csrf
    <label>Nombre completo<input name="name" required></label><label>Correo corporativo<input name="email" type="email" required></label>
    <label>Función<select name="role">@foreach(['operations'=>'Operaciones','support'=>'Soporte','security'=>'Seguridad','billing'=>'Facturación','auditor'=>'Auditor','platform_owner'=>'Propietario de plataforma'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
    <button class="cc-button cc-button-primary">Enviar invitación de 24 horas</button>
</form></section>
<section class="cc-panel cc-role-guide"><header><div><span class="cc-overline">Separación de funciones</span><h2>Capacidades</h2></div></header><dl><div><dt>Operaciones</dt><dd>Empresas, entidades e invitaciones.</dd></div><div><dt>Soporte</dt><dd>Solicita accesos JIT; no los aprueba.</dd></div><div><dt>Seguridad</dt><dd>Dominios, auditoría y equipo interno.</dd></div><div><dt>Facturación</dt><dd>Planes, puestos y suscripciones.</dd></div><div><dt>Auditor</dt><dd>Lectura de portfolio y evidencia.</dd></div></dl></section>
</div>
<section class="cc-panel"><header><div><span class="cc-overline">Directorio interno</span><h2>Identidades de plataforma</h2></div></header><div class="cc-user-list">@foreach($users as $user)<article><div class="cc-user-avatar">{{ Str::upper(Str::substr($user->name,0,2)) }}</div><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div><span>{{ str_replace('_',' ',$user->role) }}</span><span class="cc-status cc-status-{{ $user->status === 'active' ? 'active' : ($user->status === 'invited' ? 'onboarding' : 'suspended') }}">{{ $user->status }}</span><small>{{ $user->mfa_enabled ? 'MFA activo' : 'MFA pendiente' }}</small>@if($user->status !== 'disabled' && !$user->is(auth('platform')->user()))<form method="POST" action="{{ route('control.users.disable',$user) }}">@csrf @method('DELETE')<button class="cc-link cc-danger">Deshabilitar</button></form>@endif</article>@endforeach</div></section>
@endsection
