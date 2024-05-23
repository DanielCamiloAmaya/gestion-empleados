<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        @if (Auth::guard('admin')->check())
        <a class="navbar-brand" href="/admin/home">Gestión Empleados</a>
        @else
        <a class="navbar-brand" href="/home">Gestión Empleados</a>
        @endif
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if (Auth::guard('admin')->check())
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/admin/home">Home</a>
                </li>
                @else
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/home">Home</a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('departamentos.index') }}">Departamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('empleados.index') }}">Empleados</a>
                </li>
            </ul>
            <form class="d-flex">
                <ul class="navbar-nav me-5 mb-2 mb-lg-0">
                    @auth
                        @if (Auth::guard('admin')->check())
                            <li class="nav-item dropdown me-5">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ auth()->guard('admin')->user()->name }}
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="/admin/logout">Logout</a></li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/logout">Logout</a></li>
                                </ul>
                            </li>
                        @endif
                    @endauth
                </ul>
            </form>
        </div>
    </div>
</nav>

