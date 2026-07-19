@extends('control-center.layouts.app')
@section('title', $organization->name)
@section('eyebrow', 'Empresa · '.$organization->slug)
@section('page-title', $organization->name)
@section('content')
<a class="cc-back" href="{{ route('control.dashboard') }}">← Volver al portfolio</a>
@if(session('owner_invitation_url'))<section class="cc-local-link"><strong>Enlace de activación disponible en entorno local</strong><a href="{{ session('owner_invitation_url') }}">{{ session('owner_invitation_url') }}</a></section>@endif
<section class="cc-org-hero">
    <div class="cc-org-monogram">{{ Str::upper(Str::substr($organization->name, 0, 2)) }}</div>
    <div><span class="cc-status cc-status-{{ $organization->lifecycle_status }}">{{ $organization->lifecycle_status }}</span><h2>{{ $organization->legal_name ?: $organization->name }}</h2><p>{{ $organization->country_code }} · {{ $organization->timezone }} · Workspace <code>{{ $organization->slug }}</code></p></div>
    <div class="cc-org-numbers"><span><strong>{{ $organization->employees_count }}</strong>personas</span><span><strong>{{ $organization->admins_count }}</strong>administradores</span><span><strong>{{ $organization->legalEntities->count() }}</strong>entidades</span></div>
</section>
@php
    $ready = [
        ['Entidad legal', $organization->legalEntities->contains('verification_status', 'verified')],
        ['Dominio', $organization->domains->contains('verification_status', 'verified')],
        ['Propietario', $organization->admins()->where('role', 'owner')->exists()],
        ['Suscripción', $organization->subscription && !in_array($organization->subscription->status, ['canceled','past_due'])],
    ];
@endphp
<section class="cc-readiness"><div><span class="cc-overline">Activation gate</span><h2>Preparación operativa</h2></div>@foreach($ready as [$label,$passed])<span class="{{ $passed ? 'is-ready' : '' }}"><i>{{ $passed ? '✓' : '·' }}</i>{{ $label }}</span>@endforeach</section>

@if(auth('platform')->user()->hasPermission('organizations.manage'))
<section class="cc-panel cc-lifecycle-control"><header><div><span class="cc-overline">Ciclo de vida</span><h2>Estado de la empresa</h2></div><span class="cc-status cc-status-{{ $organization->lifecycle_status }}">{{ $organization->lifecycle_status }}</span></header>
    @if($organization->lifecycle_status === 'onboarding')
    <form method="POST" action="{{ route('control.organizations.transition', $organization) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><p>La activación solo se permite cuando los cuatro controles anteriores están completos.</p><button class="cc-button cc-button-primary">Activar empresa</button></form>
    @elseif($organization->lifecycle_status === 'active')
    <form method="POST" action="{{ route('control.organizations.transition', $organization) }}" class="cc-inline-action">@csrf @method('PATCH')<input type="hidden" name="status" value="suspended"><label>Motivo obligatorio<input name="reason" required minlength="10" placeholder="Incumplimiento contractual, solicitud del cliente…"></label><button class="cc-button cc-button-danger">Suspender acceso</button></form>
    @elseif($organization->lifecycle_status === 'suspended')
    <div class="cc-suspension-note"><strong>Motivo registrado</strong><p>{{ $organization->suspension_reason }}</p><time>{{ $organization->suspended_at?->format('d/m/Y H:i') }}</time></div>
    <div class="cc-action-pair"><form method="POST" action="{{ route('control.organizations.transition', $organization) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><button class="cc-button cc-button-primary">Reactivar</button></form><form method="POST" action="{{ route('control.organizations.transition', $organization) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="offboarded"><button class="cc-button cc-button-danger">Cerrar definitivamente</button></form></div>
    @else<p>El workspace fue retirado. Sus registros se conservan según la política de retención y no puede reactivarse desde este panel.</p>@endif
</section>
@endif

<div class="cc-two-columns cc-align-start">
<section class="cc-panel"><header><div><span class="cc-overline">Identidad corporativa</span><h2>Entidades legales</h2></div></header><div class="cc-entity-list">
    @foreach($organization->legalEntities as $entity)<article><div><span class="cc-status cc-status-{{ $entity->verification_status === 'verified' ? 'active' : 'onboarding' }}">{{ $entity->verification_status }}</span><strong>{{ $entity->legal_name }}</strong><small>{{ $entity->country_code }} · {{ $entity->tax_id_type }} {{ $entity->tax_identifier }}</small></div>@if($entity->verification_status !== 'verified' && auth('platform')->user()->hasPermission('legal_entities.manage'))<form method="POST" action="{{ route('control.legal-entities.verify',$entity) }}">@csrf @method('PATCH')<button class="cc-button cc-button-secondary cc-button-small">Marcar verificada</button></form>@endif</article>@endforeach
</div>
@if(auth('platform')->user()->hasPermission('legal_entities.manage'))<details class="cc-drawer"><summary>Agregar otra entidad legal</summary><form method="POST" action="{{ route('control.legal-entities.store',$organization) }}" class="cc-form-grid">@csrf
    <label class="cc-span-2">Razón social<input name="legal_name" required></label><label>Nombre comercial<input name="trade_name"></label><label>País<input name="country_code" maxlength="2" value="{{ $organization->country_code }}" required></label><label>Tipo fiscal<select name="tax_id_type">@foreach(['NIT','RUT','RFC','EIN','VAT','OTHER'] as $type)<option>{{ $type }}</option>@endforeach</select></label><label>Identificación<input name="tax_identifier" required></label><label>Número mercantil<input name="registration_number"></label><label class="cc-span-2">Dirección<textarea name="registered_address"></textarea></label><button class="cc-button cc-button-primary">Agregar entidad</button>
</form></details>@endif
</section>

<section class="cc-panel"><header><div><span class="cc-overline">Prueba de control</span><h2>Dominios empresariales</h2></div></header><div class="cc-domain-list">
@forelse($organization->domains as $domain)<article><div><span class="cc-status cc-status-{{ $domain->verification_status === 'verified' ? 'active' : 'onboarding' }}">{{ $domain->verification_status }}</span><strong>{{ $domain->domain }}</strong>@if($domain->verification_status !== 'verified')<small>TXT · {{ $domain->dnsRecordName() }}</small><code>{{ $domain->dnsRecordValue() }}</code>@endif</div>@if($domain->verification_status !== 'verified' && auth('platform')->user()->hasPermission('domains.manage'))<form method="POST" action="{{ route('control.domains.verify',$domain) }}">@csrf<button class="cc-button cc-button-secondary cc-button-small">Comprobar DNS</button></form>@endif</article>@empty<div class="cc-empty">No hay dominios registrados.</div>@endforelse
</div>
@if(auth('platform')->user()->hasPermission('domains.manage'))<form method="POST" action="{{ route('control.domains.store',$organization) }}" class="cc-inline-add">@csrf<label>Nuevo dominio<input name="domain" required placeholder="empresa.com"></label><button class="cc-button cc-button-primary">Generar verificación</button></form>@endif
</section>
</div>

<div class="cc-two-columns cc-align-start">
<section class="cc-panel"><header><div><span class="cc-overline">Cuenta propietaria</span><h2>Entrega de administración</h2></div></header><div class="cc-invite-list">@forelse($organization->ownerInvitations as $invitation)<article><div><strong>{{ $invitation->name }}</strong><small>{{ $invitation->email }} · vence {{ $invitation->expires_at->format('d/m H:i') }}</small></div><span class="cc-status cc-status-{{ $invitation->status === 'accepted' ? 'active' : ($invitation->status === 'revoked' ? 'suspended' : 'onboarding') }}">{{ $invitation->status }}</span>@if($invitation->status === 'pending' && auth('platform')->user()->hasPermission('invitations.manage'))<form method="POST" action="{{ route('control.owner-invitations.revoke',$invitation) }}">@csrf @method('DELETE')<button class="cc-link cc-danger">Revocar</button></form>@endif</article>@empty<div class="cc-empty">No hay invitaciones.</div>@endforelse</div>
@if(auth('platform')->user()->hasPermission('invitations.manage'))<form method="POST" action="{{ route('control.owner-invitations.store',$organization) }}" class="cc-inline-add">@csrf<label>Nombre<input name="name" required></label><label>Correo corporativo<input name="email" type="email" required></label><button class="cc-button cc-button-primary">Enviar invitación</button></form>@endif
</section>

<section class="cc-panel"><header><div><span class="cc-overline">Contrato y límites</span><h2>Suscripción</h2></div></header>
@if(auth('platform')->user()->hasPermission('subscriptions.manage'))<form method="POST" action="{{ route('control.subscriptions.update',$organization) }}" class="cc-form-grid">@csrf @method('PUT')
    <label>Plan<select name="plan_id">@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($organization->subscription?->plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select></label>
    <label>Puestos<input name="licensed_seats" type="number" min="1" value="{{ $organization->subscription?->licensed_seats ?? $organization->seat_limit }}" required></label>
    <label>Ciclo<select name="billing_cycle">@foreach(['monthly'=>'Mensual','annual'=>'Anual','contract'=>'Contrato'] as $value=>$label)<option value="{{ $value }}" @selected($organization->subscription?->billing_cycle === $value)>{{ $label }}</option>@endforeach</select></label>
    <label>Estado<select name="status">@foreach(['trialing','active','past_due','paused','canceled'] as $state)<option @selected($organization->subscription?->status === $state)>{{ $state }}</option>@endforeach</select></label>
    <button class="cc-button cc-button-primary">Guardar contrato</button>
</form>@else<div class="cc-contract-summary"><strong>{{ $organization->subscription?->plan?->name }}</strong><span>{{ number_format($organization->subscription?->licensed_seats ?? 0) }} puestos · {{ $organization->subscription?->billing_cycle }}</span></div>@endif
</section>
</div>

@if(auth('platform')->user()->hasPermission('support.manage'))
<section class="cc-panel cc-support-panel"><header><div><span class="cc-overline">Just-in-time access</span><h2>Acceso temporal de soporte</h2><p>La solicitud no concede acceso. Un propietario del cliente debe aprobarla antes del vencimiento.</p></div></header>
<form method="POST" action="{{ route('control.support.store',$organization) }}" class="cc-support-form">@csrf
    <label>Especialista<select name="platform_user_id">@foreach($supportUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->role }}</option>@endforeach</select></label>
    <label>Ticket<input name="ticket_reference" required placeholder="SUP-2048"></label>
    <label>Duración<select name="duration_minutes"><option value="15">15 minutos</option><option value="30">30 minutos</option><option value="60">1 hora</option><option value="120">2 horas</option><option value="240">4 horas</option></select></label>
    <label class="cc-span-3">Motivo detallado<textarea name="reason" required minlength="20" placeholder="Diagnóstico solicitado por el cliente, alcance y resultado esperado."></textarea></label>
    <fieldset class="cc-span-3"><legend>Alcances de solo lectura</legend>@foreach(['organization.read'=>'Resumen de organización','configuration.read'=>'Configuración','audit.read'=>'Auditoría','integration.diagnostics'=>'Diagnóstico de integraciones'] as $scope=>$label)<label><input type="checkbox" name="scopes[]" value="{{ $scope }}"> {{ $label }}</label>@endforeach</fieldset>
    <button class="cc-button cc-button-primary">Solicitar aprobación al cliente</button>
</form>
<div class="cc-grant-list">@foreach($organization->supportAccessGrants as $grant)<article><div><span class="cc-status cc-status-{{ $grant->status === 'approved' ? 'active' : ($grant->status === 'pending' ? 'onboarding' : 'suspended') }}">{{ $grant->status }}</span><strong>{{ $grant->ticket_reference }} · {{ $grant->platformUser->name }}</strong><small>{{ implode(', ', $grant->scopes) }} · {{ $grant->status === 'pending' ? 'aprobación disponible hasta' : 'vence' }} {{ $grant->expires_at->format('d/m H:i') }}</small><p>{{ $grant->reason }}</p></div><div class="cc-action-pair">@if($grant->isUsable() && $grant->platform_user_id === auth('platform')->id())<a class="cc-button cc-button-primary cc-button-small" href="{{ route('control.support.session',$grant) }}">Abrir sesión</a>@endif @if(!in_array($grant->status,['revoked','rejected']) && !$grant->expires_at->isPast())<form method="POST" action="{{ route('control.support.revoke',$grant) }}">@csrf @method('DELETE')<button class="cc-button cc-button-danger cc-button-small">Revocar</button></form>@endif</div></article>@endforeach</div>
</section>
@endif
@endsection
