@extends('control-center.layouts.app')
@section('title', 'Alta de empresa')
@section('eyebrow', 'Provisionamiento controlado')
@section('page-title', 'Nueva empresa')
@section('content')
<a class="cc-back" href="{{ route('control.dashboard') }}">← Volver al portfolio</a>
<section class="cc-create-intro"><div><span class="cc-overline">Onboarding empresarial</span><h2>Un workspace, una identidad legal verificable.</h2><p>La cuenta propietaria se entrega mediante invitación de un solo uso. PeopleOS nunca define ni comunica su contraseña.</p></div><div class="cc-step-rail"><span class="active">01 Empresa</span><span>02 Propietario</span><span>03 Verificación</span><span>04 Activación</span></div></section>
<form method="POST" action="{{ route('control.organizations.store') }}" class="cc-provision-form">@csrf
    <section class="cc-panel"><header><div><span class="cc-overline">01 · Workspace</span><h2>Identidad operativa</h2></div></header><div class="cc-form-grid">
        <label>Nombre comercial<input name="name" value="{{ old('name') }}" required placeholder="Acme Colombia"></label>
        <label>Workspace<input name="slug" value="{{ old('slug') }}" required pattern="[a-z0-9-]+" placeholder="acme-colombia"><small>Se usará para iniciar sesión.</small></label>
        <label>Zona horaria<select name="timezone"><option value="America/Bogota">America/Bogota</option><option value="America/Mexico_City">America/Mexico_City</option><option value="America/Lima">America/Lima</option><option value="America/New_York">America/New_York</option><option value="Europe/Madrid">Europe/Madrid</option></select></label>
        <label>Idioma<select name="locale"><option value="es">Español</option><option value="en">English</option><option value="pt">Português</option></select></label>
    </div></section>
    <section class="cc-panel"><header><div><span class="cc-overline">02 · Entidad principal</span><h2>Registro legal</h2></div></header><div class="cc-form-grid">
        <label class="cc-span-2">Razón social<input name="legal_name" value="{{ old('legal_name') }}" required placeholder="Acme Colombia S.A.S."></label>
        <label>País<input name="country_code" value="{{ old('country_code', 'CO') }}" required maxlength="2" placeholder="CO"></label>
        <label>Tipo fiscal<select name="tax_id_type">@foreach(['NIT','RUT','RFC','EIN','VAT','OTHER'] as $type)<option>{{ $type }}</option>@endforeach</select></label>
        <label>Identificación fiscal<input name="tax_identifier" value="{{ old('tax_identifier') }}" required placeholder="900123456-7"></label>
        <label class="cc-span-2">Dirección registrada<textarea name="registered_address" placeholder="Dirección legal completa">{{ old('registered_address') }}</textarea></label>
    </div></section>
    <section class="cc-panel"><header><div><span class="cc-overline">03 · Contrato</span><h2>Plan y capacidad</h2></div></header><div class="cc-plan-selector">@foreach($plans as $plan)<label><input type="radio" name="plan_id" value="{{ $plan->id }}" @checked(old('plan_id', $plans->last()?->id) == $plan->id)><span><strong>{{ $plan->name }}</strong><small>{{ $plan->description }}</small><b>{{ number_format($plan->included_seats) }} puestos incluidos</b></span></label>@endforeach</div><div class="cc-form-grid">
        <label>Puestos contratados<input name="licensed_seats" type="number" min="1" max="100000" value="{{ old('licensed_seats', 100) }}" required></label>
        <label>Ciclo de facturación<select name="billing_cycle"><option value="annual">Anual</option><option value="monthly">Mensual</option><option value="contract">Contrato personalizado</option></select></label>
    </div></section>
    <section class="cc-panel"><header><div><span class="cc-overline">04 · Responsable designado</span><h2>Primer propietario</h2></div></header><div class="cc-form-grid">
        <label>Nombre completo<input name="owner_name" value="{{ old('owner_name') }}" required></label>
        <label>Correo corporativo<input name="owner_email" type="email" value="{{ old('owner_email') }}" required></label>
    </div></section>
    <div class="cc-submit-bar"><div><strong>La empresa se creará en estado onboarding.</strong><span>La activación requiere una decisión separada y quedará auditada.</span></div><button class="cc-button cc-button-primary" type="submit">Crear empresa y enviar invitación</button></div>
</form>
@endsection
