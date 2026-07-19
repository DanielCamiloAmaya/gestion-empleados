<aside class="sidebar" data-sidebar>
    @php
        $planFeatures = app(\App\Support\OrganizationContext::class)->organization()?->subscription?->plan?->features ?? [];
        $hasPlanFeature = fn (string $feature) => in_array('all', $planFeatures, true) || in_array($feature, $planFeatures, true);
    @endphp
    <a class="brand" href="{{ auth('admin')->check() ? route('admin.home') : route('home') }}" aria-label="PeopleOS inicio">
        <span class="brand-mark">P</span>
        <span><strong>PeopleOS</strong><small>Human Capital</small></span>
    </a>

    <nav class="primary-nav" aria-label="Navegación principal">
        <p class="nav-label">Workspace</p>
        @if(auth('admin')->check())
            <a href="{{ route('admin.home') }}" @class(['active' => request()->routeIs('admin.home')])>
                <span class="nav-icon">⌂</span><span>Resumen</span>
            </a>
        @else
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>
                <span class="nav-icon">⌂</span><span>Mi espacio</span>
            </a>
        @endif
        <a href="{{ route('empleados.index') }}" @class(['active' => request()->routeIs('empleados.*')])>
            <span class="nav-icon">◎</span><span>Personas</span>
        </a>
        <a href="{{ route('departamentos.index') }}" @class(['active' => request()->routeIs('departamentos.*')])>
            <span class="nav-icon">▦</span><span>Departamentos</span>
        </a>

        <p class="nav-label nav-label-spaced">Operación</p>
        <a href="{{ route('approvals.index') }}" @class(['active' => request()->routeIs('approvals.*')])>
            <span class="nav-icon">✓</span><span>Decisiones</span>
        </a>
        @if($hasPlanFeature('leave'))<a href="{{ route('leave.index') }}" @class(['active' => request()->routeIs('leave.*')])>
            <span class="nav-icon">◌</span><span>Solicitudes</span>
        </a>@endif
        @if($hasPlanFeature('onboarding'))<a href="{{ route('onboarding.index') }}" @class(['active' => request()->routeIs('onboarding.*')])>
            <span class="nav-icon">↗</span><span>Onboarding</span>
        </a>@endif
        <a href="{{ route('goals.index') }}" @class(['active' => request()->routeIs('goals.*')])>
            <span class="nav-icon">⌾</span><span>Objetivos</span>
        </a>
        @if($hasPlanFeature('documents'))<a href="{{ route('documents.index') }}" @class(['active' => request()->routeIs('documents.*')])>
            <span class="nav-icon">▤</span><span>Documentos</span>
        </a>@endif
        @if($hasPlanFeature('reviews'))<a href="{{ route('reviews.index') }}" @class(['active' => request()->routeIs('reviews.*')])>
            <span class="nav-icon">◎</span><span>Evaluaciones</span>
        </a>@endif
        @if($hasPlanFeature('talent'))<a href="{{ route('expansion.index') }}" @class(['active' => request()->routeIs('expansion.*','attendance.*','courses.*','jobs.*','candidates.*','compensation.*')])>
            <span class="nav-icon">✦</span><span>Talento+</span>
        </a>@endif

        @if(auth('admin')->check())
            <p class="nav-label nav-label-spaced">Gobierno</p>
            <a href="{{ route('audit.index') }}" @class(['active' => request()->routeIs('audit.*')])>
                <span class="nav-icon">◇</span><span>Auditoría</span>
            </a>
            @if($hasPlanFeature('offboarding'))<a href="{{ route('offboarding.index') }}" @class(['active' => request()->routeIs('offboarding.*')])>
                <span class="nav-icon">↘</span><span>Offboarding</span>
            </a>@endif
            <a href="{{ route('reports.index') }}" @class(['active' => request()->routeIs('reports.*')])>
                <span class="nav-icon">⌁</span><span>Analítica</span>
            </a>
            <a href="{{ route('mfa.settings') }}" @class(['active' => request()->routeIs('mfa.settings')])>
                <span class="nav-icon">⌾</span><span>Seguridad</span>
            </a>
            @if(auth('admin')->user()->hasPermission('integrations.manage') && ($hasPlanFeature('api') || $hasPlanFeature('sso') || $hasPlanFeature('advanced_audit')))<a href="{{ route('platform.index') }}" @class(['active' => request()->routeIs('platform.*')])><span class="nav-icon">⌬</span><span>Plataforma</span></a>@endif
            @if(auth('admin')->user()->hasPermission('security.manage'))<a href="{{ route('access.index') }}" @class(['active' => request()->routeIs('access.*')])><span class="nav-icon">⊕</span><span>Accesos</span></a>@endif
            @if(auth('admin')->user()->hasPermission('security.manage'))<a href="{{ route('support-access.index') }}" @class(['active' => request()->routeIs('support-access.*')])><span class="nav-icon">JIT</span><span>Soporte</span></a>@endif
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="security-badge"><span>✓</span><div><strong>Espacio protegido</strong><small>Sesión cifrada y auditada</small></div></div>
        <form method="POST" action="{{ auth('admin')->check() ? route('admin.logout') : route('logout') }}">
            @csrf
            <button class="logout-button" type="submit"><span>↪</span> Cerrar sesión</button>
        </form>
    </div>
</aside>
<button class="sidebar-backdrop" type="button" data-menu-toggle aria-label="Cerrar navegación"></button>
