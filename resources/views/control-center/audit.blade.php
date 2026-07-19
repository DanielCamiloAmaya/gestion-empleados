@extends('control-center.layouts.app')
@section('title', 'Auditoría')
@section('eyebrow', 'Evidencia append-only')
@section('page-title', 'Auditoría de plataforma')
@section('content')
<section class="cc-audit-hero"><div><span class="cc-overline">Integridad criptográfica</span><h2>{{ $chainValid ? 'La cadena de evidencia está íntegra.' : 'La cadena requiere investigación.' }}</h2><p>Cada evento incorpora el hash del evento anterior. Las modificaciones y eliminaciones están bloqueadas en el modelo.</p></div><span class="cc-chain-state {{ $chainValid ? 'is-ok' : 'is-risk' }}">{{ $chainValid ? 'VERIFICADA' : 'RIESGO' }}</span></section>
<section class="cc-panel"><header><div><span class="cc-overline">Ledger de plataforma</span><h2>Eventos registrados</h2></div></header><div class="cc-audit-table"><div class="cc-audit-head"><span>Evento</span><span>Actor</span><span>Empresa</span><span>Momento</span><span>Hash</span></div>@foreach($events as $event)<article><div><strong>{{ str_replace(['.','_'],' ',$event->event) }}</strong><small>{{ class_basename($event->target_type) }} #{{ $event->target_id ?: 'system' }}</small></div><span>{{ $event->actor_name }}</span><span>{{ $event->organization?->name ?? 'Plataforma' }}</span><time>{{ $event->created_at->format('d/m/Y H:i:s') }}</time><code title="{{ $event->entry_hash }}">{{ Str::substr($event->entry_hash,0,12) }}…</code></article>@endforeach</div></section>
{{ $events->links() }}
@endsection
