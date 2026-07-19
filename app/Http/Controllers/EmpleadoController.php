<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\User;
use App\Services\AccessLifecycleService;
use App\Services\PlanEnforcementService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['departamento', 'manager']);

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($query) use ($search) {
                $query->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhereHas('departamento', fn ($department) => $department->where('nombre', 'like', "%{$search}%"));
            });
        });

        $query->when($request->filled('department'), fn ($query) => $query->where('departamento_id', $request->integer('department')));
        $query->when($request->filled('status'), fn ($query) => $query->where('employment_status', $request->string('status')));

        return view('empleados.index', [
            'empleados' => $query->orderBy('first_name')->orderBy('last_name')->paginate(12)->withQueryString(),
            'departamentos' => Departamento::where('is_active', true)->orderBy('nombre')->get(),
        ]);
    }

    public function show(User $empleado)
    {
        $empleado->load(['departamento', 'manager', 'directReports.departamento']);

        return view('empleados.show', compact('empleado'));
    }

    public function create()
    {
        return view('empleados.create', $this->formOptions());
    }

    public function store(EmployeeStoreRequest $request, PlanEnforcementService $plans)
    {
        $plans->assertCanAddEmployees(app(OrganizationContext::class)->organization());
        $employee = DB::transaction(function () use ($request) {
            $employee = User::create($request->validated());
            AuditLog::record($request, 'employee.created', $employee, [], $employee->toArray());

            return $employee;
        });

        return redirect()->route('empleados.show', $employee)->with('success', 'Empleado creado y acceso habilitado correctamente.');
    }

    public function edit(User $empleado)
    {
        return view('empleados.edit', ['empleado' => $empleado, ...$this->formOptions($empleado)]);
    }

    public function update(EmployeeUpdateRequest $request, User $empleado)
    {
        DB::transaction(function () use ($request, $empleado) {
            $old = $empleado->toArray();
            $data = $request->validated();

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $empleado->update($data);
            AuditLog::record($request, 'employee.updated', $empleado, $old, $empleado->fresh()->toArray());
        });

        return redirect()->route('empleados.show', $empleado)->with('success', 'Perfil laboral actualizado.');
    }

    public function destroy(Request $request, User $empleado, AccessLifecycleService $access)
    {
        DB::transaction(function () use ($request, $empleado, $access) {
            $old = $empleado->toArray();
            $empleado->update(['employment_status' => 'inactive']);
            $access->revokeEmployee($empleado);
            $empleado->delete();
            AuditLog::record($request, 'employee.archived', $empleado, $old, ['employment_status' => 'inactive']);
        });

        return redirect()->route('empleados.index')->with('success', 'Empleado archivado. Su historial permanece disponible para auditoría.');
    }

    private function formOptions(?User $employee = null): array
    {
        return [
            'departamentos' => Departamento::where('is_active', true)->orderBy('nombre')->get(),
            'managers' => User::where('employment_status', 'active')
                ->when($employee, fn ($query) => $query->whereKeyNot($employee->id))
                ->orderBy('first_name')->orderBy('last_name')->get(),
        ];
    }
}
