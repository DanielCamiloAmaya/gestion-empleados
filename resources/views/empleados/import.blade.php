@extends('layouts.app-master')
@section('title', 'Importar personas')
@section('eyebrow', 'Migración controlada')
@section('page-title', 'Importar personas')
@section('content')
    <div class="page-heading reveal-item"><div><h2>Incorporación masiva sin contraseñas expuestas</h2><p>Validamos todas las filas antes de escribir. Al finalizar recibirás un CSV con enlaces de activación de uso único.</p></div><a class="button button-secondary" href="{{ route('employees.import.template') }}">Descargar plantilla</a></div>
    <div class="form-shell reveal-item">
        <section class="panel form-panel"><form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data" class="stack-form">@csrf<div class="field"><label for="file">Archivo CSV</label><input class="input file-input" id="file" name="file" type="file" accept=".csv,text/csv" required><small>UTF-8, Windows-1252 o ISO-8859-1. Máximo 5 MB y 2.000 filas.</small></div><div class="import-contract"><strong>Contrato de importación</strong><code>employee_code · first_name · last_name · email · username · job_title · department · employment_type · hire_date · phone · location</code></div><button class="button button-primary" type="submit">Validar e importar</button></form></section>
        <aside class="panel guidance-panel"><span class="eyebrow">Seguridad</span><h3>Qué sucede después</h3><ol class="numbered-guidance"><li><span>1</span>Se valida estructura, duplicados y departamentos.</li><li><span>2</span>El lote completo se confirma en una transacción.</li><li><span>3</span>Cada persona recibe una invitación con vigencia de siete días.</li></ol><p>PeopleOS nunca incluye contraseñas en la plantilla ni en la exportación.</p></aside>
    </div>
@endsection
