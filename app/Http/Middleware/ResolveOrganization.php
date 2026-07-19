<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('control-center*') || $request->routeIs('control.*', 'organization-owner-invitations.*')) {
            app(OrganizationContext::class)->clear();

            return $next($request);
        }

        $slug = $request->input('workspace')
            ?? $request->session()->get('organization.slug')
            ?? $request->cookie('peopleos_workspace')
            ?? config('app.default_organization', 'peopleos-demo');

        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $organization) {
            if ($request->isMethod('post') && ($request->routeIs('login') || $request->routeIs('admin.login'))) {
                return back()->withInput($request->except('password'))->withErrors([
                    'workspace' => 'El espacio de trabajo no existe o se encuentra suspendido.',
                ]);
            }

            abort(404, 'Espacio de trabajo no disponible.');
        }

        app(OrganizationContext::class)->set($organization);
        $request->session()->put([
            'organization.id' => $organization->id,
            'organization.slug' => $organization->slug,
        ]);

        $actor = auth('admin')->user() ?? auth()->user();
        if ($actor && (int) $actor->organization_id !== (int) $organization->id) {
            auth('admin')->logout();
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'La sesion no pertenece a este espacio de trabajo.');
        }

        $response = $next($request);
        $response->headers->setCookie(cookie(
            'peopleos_workspace', $organization->slug, 60 * 24 * 30, '/', null,
            (bool) config('session.secure'), true, false, 'lax'
        ));

        return $response;
    }
}
