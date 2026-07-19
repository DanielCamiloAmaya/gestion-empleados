<?php

namespace App\Http\Middleware;

use App\Services\PlanEnforcementService;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(private readonly PlanEnforcementService $plans) {}

    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $organization = app(OrganizationContext::class)->organization();
        $allowed = $organization && collect($features)->contains(
            fn (string $feature) => $this->plans->allowsFeature($organization, $feature)
        );
        if (! $allowed) {
            $message = 'La función solicitada no está incluida en el plan activo de la empresa.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'code' => 'plan_feature_required'], 402);
            }

            abort(402, $message);
        }

        return $next($request);
    }
}
