<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string $ability = '*'): Response
    {
        $plain = $request->bearerToken();
        if (! $plain) {
            return response()->json(['message' => 'Missing bearer token'], 401);
        }$token = ApiToken::withoutGlobalScope('organization')->where('token_hash', hash('sha256', $plain))->whereNull('revoked_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
        if (! $token || ! $token->can($ability)) {
            return response()->json(['message' => 'Invalid token or insufficient scope'], 403);
        }$organization = Organization::whereKey($token->organization_id)->where('is_active', true)->first();
        if (! $organization) {
            return response()->json(['message' => 'Organization unavailable'], 403);
        }app(OrganizationContext::class)->set($organization);
        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
