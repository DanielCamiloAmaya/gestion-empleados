<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminInvitation;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    public function index()
    {
        return view('security.access', ['roles' => Role::with(['permissions', 'admins'])->orderByDesc('is_system')->orderBy('name')->get(), 'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'), 'admins' => Admin::with('roles')->orderBy('name')->get(), 'invitations' => AdminInvitation::with('role')->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->latest()->get()]);
    }

    public function role(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'], 'permissions' => ['required', 'array', 'min:1'], 'permissions.*' => ['integer', Rule::exists('permissions', 'id')]]);
        $role = Role::create([...$d, 'slug' => Str::slug($d['name']).'-'.Str::lower(Str::random(4)), 'is_system' => false]);
        $role->permissions()->sync($d['permissions']);
        AuditLog::record($r, 'security.role_created', $role, [], ['permissions' => $d['permissions']]);

        return back()->with('success', 'Rol personalizado creado.');
    }

    public function assign(Request $r, Admin $admin)
    {
        abort_if($admin->role === 'owner', 422, 'El propietario conserva su rol de sistema.');
        $d = $r->validate(['roles' => ['required', 'array', 'min:1'], 'roles.*' => ['integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('organization_id', auth('admin')->user()->organization_id))]]);
        $old = $admin->roles()->pluck('roles.id')->all();
        $admin->roles()->sync($d['roles']);
        AuditLog::record($r, 'security.roles_assigned', $admin, ['roles' => $old], ['roles' => $d['roles']]);

        return back()->with('success', 'Permisos administrativos actualizados.');
    }

    public function mfaPolicy(Request $request)
    {
        $organization = auth('admin')->user()->organization;
        $settings = $organization->settings ?? [];
        $settings['require_admin_mfa'] = $request->boolean('require_admin_mfa');
        $settings['require_employee_mfa'] = $request->boolean('require_employee_mfa');
        $organization->update(['settings' => $settings]);
        AuditLog::record($request, 'security.mfa_policy_updated', $organization, [], [
            'require_admin_mfa' => $settings['require_admin_mfa'],
            'require_employee_mfa' => $settings['require_employee_mfa'],
        ]);

        return back()->with('success', 'Política MFA actualizada.');
    }
}
