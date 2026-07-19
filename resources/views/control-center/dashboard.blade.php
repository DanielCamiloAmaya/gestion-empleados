@extends('control-center.layouts.app')
@section('title', 'Empresas')
@section('eyebrow', 'Portfolio operativo')
@section('page-title', 'Empresas')
@section('content')
@if(session('recovery_codes'))
<section class="cc-recovery" aria-labelledby="recovery-title"><div><span class="cc-overline">Mostrar una sola vez</span><h2 id="recovery-title">Códigos de recuperación</h2><p>Guárdalos en el gestor corporativo de secretos.</p></div><div>@foreach(session('recovery_codes') as $code)<code>{{ $code }}</code>@endforeach</div></section>
@endif
<section class="cc-command-hero">
    <div><span class="cc-overline">Estado operacional global</span><h2>Gobierno visible, acceso explícito.</h2><p>Cada empresa avanza por un ciclo controlado; ninguna cuenta interna hereda acceso a sus datos.</p></div>
    <div class="cc-integrity"><span class="{{ $auditChainValid ? 'is-ok' : 'is-risk' }}"></span><strong>{{ $auditChainValid ? 'Cadena íntegra' : 'Revisar integridad' }}</strong><small>Auditoría criptográfica</small></div>
</section>
<div class="cc-lifecycle" aria-label="Ciclo de empresas">
    @foreach(['onboarding' => 'Onboarding', 'active' => 'Activas', 'suspended' => 'Suspendidas', 'offboarded' => 'Retiradas'] as $key => $label)
        <a href="{{ route('control.dashboard', ['status' => $key]) }}" @class(['active' => request('status') === $key])><span>{{ $label }}</span><strong>{{ $counts[$key] ?? 0 }}</strong></a>
    @endforeach
    <div><span>Soporte por aprobar</span><strong>{{ $pendingSupport }}</strong></div>
</div>
<div class="cc-section-head"><div><span class="cc-overline">Directorio multiempresa</span><h2>Workspaces administrados</h2></div>@if(auth('platform')->user()->hasPermission('organizations.manage'))<a class="cc-button cc-button-primary" href="{{ route('control.organizations.create') }}">Dar de alta empresa</a>@endif</div>
<form class="cc-filter" method="GET">
    <label><span class="sr-only">Buscar empresa</span><input name="q" value="{{ request('q') }}" placeholder="Buscar por empresa, workspace o NIT"></label>
    <select name="status" aria-label="Estado"><option value="">Todos los estados</option>@foreach(['onboarding','active','suspended','offboarded'] as $state)<option value="{{ $state }}" @selected(request('status') === $state)>{{ ucfirst($state) }}</option>@endforeach</select>
    <button class="cc-button cc-button-secondary">Filtrar</button>
</form>
<section class="cc-org-ledger" aria-label="Empresas">
    @forelse($organizations as $organization)
    <article>
        <div class="cc-org-identity"><span>{{ Str::upper(Str::substr($organization->name, 0, 2)) }}</span><div><a href="{{ route('control.organizations.show', $organization) }}">{{ $organization->name }}</a><small>{{ $organization->slug }} · {{ $organization->country_code }}</small></div></div>
        <div><small>Plan / capacidad</small><strong>{{ $organization->subscription?->plan?->name ?? ucfirst($organization->plan) }}</strong><span>{{ $organization->employees_count }} / {{ number_format($organization->seat_limit) }} personas</span></div>
        <div><small>Identidad legal</small><strong>{{ $organization->legalEntities->count() }} entidades</strong><span>{{ $organization->domains->where('verification_status', 'verified')->count() }} dominios verificados</span></div>
        <span class="cc-status cc-status-{{ $organization->lifecycle_status }}">{{ $organization->lifecycle_status }}</span>
        <a class="cc-row-action" href="{{ route('control.organizations.show', $organization) }}" aria-label="Abrir {{ $organization->name }}">→</a>
    </article>
    @empty
    <div class="cc-empty"><strong>No hay empresas con estos criterios.</strong><span>Ajusta los filtros o inicia un nuevo onboarding.</span></div>
    @endforelse
</section>
{{ $organizations->links() }}
<div class="cc-two-columns">
    <section class="cc-panel"><header><div><span class="cc-overline">Últimos eventos</span><h2>Actividad de confianza</h2></div>@if(auth('platform')->user()->hasPermission('audit.view'))<a href="{{ route('control.audit') }}">Ver auditoría</a>@endif</header><div class="cc-event-list">@foreach($recentAudit as $event)<article><span class="cc-event-mark"></span><div><strong>{{ str_replace(['.', '_'], ' ', $event->event) }}</strong><small>{{ $event->actor_name }} · {{ $event->organization?->name ?? 'Plataforma' }}</small></div><time>{{ $event->created_at->diffForHumans() }}</time></article>@endforeach</div></section>
    <section class="cc-panel cc-principle"><span class="cc-overline">Modelo de seguridad</span><h2>El soporte solicita. El cliente decide.</h2><p>Los accesos se limitan por alcance, ticket y tiempo. La aprobación del propietario nunca se sustituye con privilegios técnicos.</p><ul><li>Sin suplantación silenciosa</li><li>Revocación inmediata</li><li>Evidencia en ambos registros</li></ul></section>
</div>
@endsection
