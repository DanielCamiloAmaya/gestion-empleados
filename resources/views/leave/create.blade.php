@extends('layouts.app-master')
@section('title', 'Nueva solicitud')
@section('eyebrow', 'Tiempo y bienestar')
@section('page-title', 'Nueva solicitud')
@section('content')
    @if(isset($balances))<section class="balance-ribbon reveal-item" aria-label="Saldos disponibles">@foreach($balances as $balance)<div><span>{{ $balance->policy->name }}</span><strong>{{ number_format($balance->available,1) }}</strong><small>días disponibles</small></div>@endforeach</section>@endif
    <div class="page-heading reveal-item"><div><h2>Planifica tu ausencia</h2><p>Indica el tipo, las fechas y el contexto que necesita RR. HH. para decidir.</p></div></div>
    <form class="form-shell reveal-item" method="POST" action="{{ route('leave.store') }}">@csrf
        <div class="form-card">
            <div class="form-section"><h3>Detalle de la solicitud</h3><p>Las fechas no pueden cruzarse con otra solicitud pendiente o aprobada.</p><div class="form-grid">
                <div class="field field-full"><label for="type">Tipo de ausencia</label><select class="select" id="type" name="type" required><option value="">Selecciona una opción</option>@foreach(['vacation','medical','personal','parental','other'] as $type)<option value="{{ $type }}" @selected(old('type')===$type)>{{ __('ops.leave_types.'.$type) }}</option>@endforeach</select></div>
                <div class="field"><label for="start_date">Desde</label><input class="input" id="start_date" type="date" name="start_date" min="{{ today()->format('Y-m-d') }}" value="{{ old('start_date') }}" required></div>
                <div class="field"><label for="end_date">Hasta</label><input class="input" id="end_date" type="date" name="end_date" min="{{ today()->format('Y-m-d') }}" value="{{ old('end_date') }}" required></div>
                <div class="field field-full"><label for="reason">Motivo y contexto</label><textarea class="textarea" id="reason" name="reason" required maxlength="1500" placeholder="Explica brevemente lo necesario para revisar tu solicitud…">{{ old('reason') }}</textarea></div>
            </div></div>
            <div class="form-actions"><a class="button button-secondary" href="{{ route('leave.index') }}">Cancelar</a><button class="button button-primary" type="submit">Enviar solicitud</button></div>
        </div>
        <aside class="aside-card"><h3>Qué ocurre después</h3><ul class="check-list"><li>RR. HH. recibe la solicitud.</li><li>La decisión queda registrada en auditoría.</li><li>Podrás consultar el resultado en esta bandeja.</li></ul></aside>
    </form>
@endsection
