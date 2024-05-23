@extends('layouts.auth-master')

@section('content')
<form action="/login" method="POST">
    @csrf
    <h1>Login</h1>
    @include('layouts.partials.messages')
    <div class=" form-floating mb-3">
    <input type="text" placeholder="username" name="username" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
    <label for="exampleInputEmail1" class="form-label">Username/ Dirección de correo electrónico</label>
    <div id="emailHelp" class="form-text">Nunca compartiremos su correo electrónico con nadie más.</div>
    </div>
    <div class=" form-floating mb-3">
    <input type="password" placeholder="password" name="password" class="form-control" id="exampleInputPassword1">
    <label for="exampleInputPassword1" class="form-label">Contraseña</label>
    </div>
    <div class="mb-3">
        <a href="/register">Crear cuenta</a>
    </div>
    <button type="submit" class="btn btn-primary">Enviar</button>
</form>    
    
@endsection  
