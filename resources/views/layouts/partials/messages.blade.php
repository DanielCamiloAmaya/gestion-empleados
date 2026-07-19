@if ($errors->any())
    <div class="notice notice-error" role="alert">
        <strong>Revisa la información</strong>
        <ul>
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div class="notice notice-success" role="status"><span>✓</span><p>{{ session('success') }}</p></div>
@endif

@if (session('error'))
    <div class="notice notice-error" role="alert"><span>!</span><p>{{ session('error') }}</p></div>
@endif
