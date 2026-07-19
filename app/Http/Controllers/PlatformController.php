<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\ComplianceControl;
use App\Models\IntegrationCatalog;
use App\Models\OrganizationIntegration;
use App\Models\SsoConnection;
use App\Models\WebhookEndpoint;
use App\Services\PlanEnforcementService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    public function index()
    {
        return view('platform.index', ['catalog' => IntegrationCatalog::where('is_active', true)->get(), 'connections' => OrganizationIntegration::with('catalog')->get(), 'tokens' => ApiToken::latest()->get(), 'webhooks' => WebhookEndpoint::latest()->get(), 'ssoConnections' => SsoConnection::latest()->get(), 'controls' => ComplianceControl::orderBy('framework')->orderBy('control_code')->get()]);
    }

    public function token(Request $r, PlanEnforcementService $plans)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'abilities' => ['required', 'array', 'min:1'], 'abilities.*' => [Rule::in(['employees:read', 'scim'])], 'expires_in_days' => ['required', 'integer', 'between:1,365']]);
        if (in_array('scim', $d['abilities'], true)) {
            $plans->assertFeature(app(OrganizationContext::class)->organization(), 'scim');
        }
        $plain = 'pos_live_'.Str::random(48);
        ApiToken::create(['created_by' => auth('admin')->id(), 'name' => $d['name'], 'token_prefix' => substr($plain, 0, 16), 'token_hash' => hash('sha256', $plain), 'abilities' => $d['abilities'], 'expires_at' => filled($d['expires_in_days'] ?? null) ? now()->addDays((int) $d['expires_in_days']) : null]);

        return back()->with('api_plain_token', $plain)->with('success', 'Token creado. Copialo ahora: no volvera a mostrarse.');
    }

    public function revoke(string $token)
    {
        $token = ApiToken::findOrFail($token);
        $token->update(['revoked_at' => now()]);

        return back()->with('success', 'Token revocado inmediatamente.');
    }

    public function webhook(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'url' => ['required', 'url:https', 'max:500'], 'events' => ['required', 'array', 'min:1'], 'events.*' => ['string', 'max:80']]);
        $this->assertPublicUrl($d['url']);
        $secret = 'whsec_'.Str::random(48);
        WebhookEndpoint::create([...$d, 'secret' => $secret]);

        return back()->with('webhook_secret', $secret)->with('success', 'Webhook activo. Guarda el secreto de firma.');
    }

    public function integration(Request $r, IntegrationCatalog $integration)
    {
        return back()->withErrors(['integration' => $integration->name.' requiere un conector certificado y credenciales del proveedor. No se marcó como configurado.']);
    }

    public function compliance(Request $r)
    {
        $d = $r->validate(['framework' => ['required', Rule::in(['ISO27001', 'SOC2', 'GDPR', 'HABEAS_DATA', 'WCAG'])], 'control_code' => ['required', 'string', 'max:40'], 'title' => ['required', 'string', 'max:180'], 'status' => ['required', Rule::in(['planned', 'in_progress', 'implemented'])], 'evidence' => ['nullable', 'required_if:status,implemented', 'string', 'min:30', 'max:5000'], 'next_review_at' => ['nullable', 'date']]);
        ComplianceControl::updateOrCreate(['framework' => $d['framework'], 'control_code' => $d['control_code']], $d + ['owner_id' => auth('admin')->id(), 'verified_by' => null, 'verified_at' => null, 'review_note' => null]);

        return back()->with('success', 'Control de cumplimiento actualizado.');
    }

    public function verifyCompliance(Request $r, ComplianceControl $control)
    {
        abort_if($control->owner_id === auth('admin')->id(), 422, 'La verificación requiere un segundo administrador independiente.');
        abort_unless($control->status === 'implemented' && mb_strlen((string) $control->evidence) >= 30, 422, 'El control debe estar implementado y contener evidencia suficiente.');
        $data = $r->validate(['review_note' => ['required', 'string', 'min:20', 'max:2000']]);
        $control->update(['status' => 'verified', 'verified_by' => auth('admin')->id(), 'verified_at' => now(), 'review_note' => $data['review_note']]);

        return back()->with('success', 'Control verificado con separación de funciones.');
    }

    public function sso(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'issuer_url' => ['required', 'url:https', 'max:500'], 'client_id' => ['required', 'string', 'max:255'], 'client_secret' => ['required', 'string', 'max:1000'], 'allowed_domains' => ['required', 'string', 'max:1000']]);
        $issuer = rtrim($d['issuer_url'], '/');
        $this->assertPublicUrl($issuer);
        $discovery = Http::timeout(10)->acceptJson()->get($issuer.'/.well-known/openid-configuration')->throw()->json();
        foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $field) {
            abort_unless(filled($discovery[$field] ?? null), 422, "El proveedor no publico {$field}.");
        }abort_unless(hash_equals($issuer, rtrim($discovery['issuer'], '/')), 422, 'El issuer descubierto no coincide con el configurado.');
        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $field) {
            $this->assertPublicUrl($discovery[$field]);
        }SsoConnection::create(['name' => $d['name'], 'issuer_url' => $issuer, 'client_id' => $d['client_id'], 'client_secret' => $d['client_secret'], 'authorization_endpoint' => $discovery['authorization_endpoint'], 'token_endpoint' => $discovery['token_endpoint'], 'jwks_uri' => $discovery['jwks_uri'], 'allowed_domains' => collect(explode(',', $d['allowed_domains']))->map(fn ($v) => Str::lower(trim($v)))->filter()->values()->all(), 'verified_at' => now(), 'is_enabled' => true]);

        return back()->with('success', 'Conexion OIDC verificada y habilitada.');
    }

    private function assertPublicUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        abort_unless($host && filter_var($url, FILTER_VALIDATE_URL), 422, 'URL invalida.');
        if (in_array(Str::lower($host), ['localhost', '127.0.0.1', '::1'], true)) {
            abort(422, 'No se permiten destinos locales.');
        }$records = dns_get_record($host, DNS_A | DNS_AAAA);
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                abort(422, 'El destino resuelve a una red privada o reservada.');
            }
        }
    }
}
