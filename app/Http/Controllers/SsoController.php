<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\SsoConnection;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function redirect(Request $r, SsoConnection $connection)
    {
        abort_unless($connection->is_enabled && $connection->verified_at, 404);
        $state = Str::random(48);
        $nonce = Str::random(48);
        $verifier = Str::random(96);
        $r->session()->put('oidc', ['connection_id' => $connection->id, 'state' => $state, 'nonce' => $nonce, 'verifier' => $verifier]);
        $query = http_build_query(['client_id' => $connection->client_id, 'redirect_uri' => route('sso.callback', ['connection' => $connection, 'workspace' => $connection->organization->slug]), 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state, 'nonce' => $nonce, 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), 'code_challenge_method' => 'S256']);

        return redirect()->away($connection->authorization_endpoint.'?'.$query);
    }

    public function callback(Request $r, SsoConnection $connection)
    {
        $pending = $r->session()->pull('oidc');
        abort_unless($pending && hash_equals($pending['state'], (string) $r->input('state')) && (int) $pending['connection_id'] === $connection->id, 403, 'Estado OIDC invalido.');
        $response = Http::asForm()->timeout(15)->post($connection->token_endpoint, ['grant_type' => 'authorization_code', 'code' => $r->string('code')->toString(), 'redirect_uri' => route('sso.callback', ['connection' => $connection, 'workspace' => $connection->organization->slug]), 'client_id' => $connection->client_id, 'client_secret' => $connection->client_secret, 'code_verifier' => $pending['verifier']])->throw()->json();
        abort_unless(filled($response['id_token'] ?? null), 403, 'El proveedor no devolvio un ID token.');
        $jwks = Http::timeout(10)->acceptJson()->get($connection->jwks_uri)->throw()->json();
        $claims = JWT::decode($response['id_token'], JWK::parseKeySet($jwks));
        abort_unless(hash_equals(rtrim($connection->issuer_url, '/'), rtrim((string) $claims->iss, '/')), 403);
        $aud = (array) $claims->aud;
        abort_unless(in_array($connection->client_id, $aud, true), 403);
        abort_unless(hash_equals($pending['nonce'], (string) ($claims->nonce ?? '')), 403);
        $email = Str::lower((string) ($claims->email ?? $claims->preferred_username ?? ''));
        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403);
        $domain = Str::afterLast($email, '@');
        abort_unless(in_array($domain, $connection->allowed_domains ?? [], true), 403, 'Dominio no autorizado.');
        $actor = Admin::where('email', $email)->first();
        $guard = 'admin';
        if (! $actor) {
            $actor = User::where('email', $email)->whereIn('employment_status', ['active', 'onboarding'])->first();
            $guard = 'web';
        }abort_unless($actor, 403, 'La identidad no esta aprovisionada en PeopleOS.');
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        Auth::guard($guard)->login($actor, false);
        $r->session()->regenerate();
        $amr = (array) ($claims->amr ?? []);
        if (! $actor->mfa_enabled || in_array('mfa', $amr, true)) {
            $r->session()->put('mfa.verified_actor', $guard.':'.$actor->id);
        }$r->session()->put(['security.guard' => $guard, 'security.version' => (int) config('session_security.version'), 'security.authenticated_at' => now()->timestamp, 'security.last_activity_at' => now()->timestamp, 'security.auth_version' => (int) $actor->auth_version, 'organization.id' => $connection->organization_id, 'organization.slug' => $connection->organization->slug]);

        return redirect()->intended($guard === 'admin' ? route('admin.home') : route('home'));
    }
}
