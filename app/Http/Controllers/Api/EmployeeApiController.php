<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessLifecycleService;
use App\Services\PlanEnforcementService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeApiController extends Controller
{
    public function index(Request $r)
    {
        $employees = User::with('departamento')->when($r->filled('updated_after'), fn ($q) => $q->where('updated_at', '>', $r->date('updated_after')))->paginate(min(max($r->integer('per_page', 50), 1), 100));

        return response()->json($employees);
    }

    public function scimIndex(Request $r)
    {
        $users = User::paginate(min(max($r->integer('count', 50), 1), 100), ['*'], 'page', max(1, (int) ceil(max($r->integer('startIndex', 1), 1) / max($r->integer('count', 50), 1))));

        return response()->json(['schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'], 'totalResults' => $users->total(), 'startIndex' => ($users->currentPage() - 1) * $users->perPage() + 1, 'itemsPerPage' => $users->count(), 'Resources' => $users->map(fn ($u) => $this->scim($u))]);
    }

    public function scimShow(string $user)
    {
        $user = User::findOrFail($user);

        return response()->json($this->scim($user));
    }

    public function scimStore(Request $r, PlanEnforcementService $plans)
    {
        $org = $r->attributes->get('api_token')->organization_id;
        $plans->assertCanAddEmployees(Organization::with('subscription.plan')->findOrFail($org));
        $department = Departamento::first();
        abort_unless($department, 422, 'Organization requires at least one department.');
        $d = $r->validate(['userName' => ['required', 'email', Rule::unique('users', 'email')->where(fn ($q) => $q->where('organization_id', $org))], 'name.givenName' => ['required', 'string', 'max:100'], 'name.familyName' => ['required', 'string', 'max:100'], 'active' => ['nullable', 'boolean'], 'title' => ['nullable', 'string', 'max:150'], 'externalId' => ['nullable', 'string', 'max:100']]);
        $user = User::create(['organization_id' => $org, 'departamento_id' => $department->id, 'employee_code' => $d['externalId'] ?? 'SCIM-'.Str::upper(Str::random(8)), 'first_name' => $d['name']['givenName'], 'last_name' => $d['name']['familyName'], 'email' => $d['userName'], 'username' => Str::before($d['userName'], '@').'.'.Str::lower(Str::random(4)), 'job_title' => $d['title'] ?? null, 'employment_status' => ($d['active'] ?? true) ? 'active' : 'inactive', 'employment_type' => 'full_time', 'password' => Str::password(40)]);

        return response()->json($this->scim($user), 201, ['Location' => route('api.scim.users.show', $user)]);
    }

    public function scimPatch(Request $r, string $user, AccessLifecycleService $access)
    {
        $user = User::findOrFail($user);
        $operations = $r->input('Operations', []);
        foreach ($operations as $op) {
            if (Str::lower($op['op'] ?? '') === 'replace' && Str::lower($op['path'] ?? '') === 'active') {
                $active = filter_var($op['value'], FILTER_VALIDATE_BOOLEAN);
                $user->update(['employment_status' => $active ? 'active' : 'inactive']);
                if (! $active) {
                    $access->revokeEmployee($user);
                }
            }
        }

        return response()->json($this->scim($user));
    }

    private function scim(User $u): array
    {
        return ['schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'], 'id' => (string) $u->id, 'externalId' => $u->employee_code, 'userName' => $u->email, 'name' => ['givenName' => $u->first_name, 'familyName' => $u->last_name, 'formatted' => $u->full_name], 'displayName' => $u->full_name, 'title' => $u->job_title, 'active' => in_array($u->employment_status, ['active', 'onboarding'], true), 'meta' => ['resourceType' => 'User', 'created' => $u->created_at?->toIso8601String(), 'lastModified' => $u->updated_at?->toIso8601String(), 'location' => route('api.scim.users.show', $u)]];
    }
}
