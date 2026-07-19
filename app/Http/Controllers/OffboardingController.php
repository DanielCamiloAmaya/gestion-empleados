<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OffboardingCase;
use App\Models\OffboardingTask;
use App\Models\User;
use App\Services\AccessLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OffboardingController extends Controller
{
    public function index()
    {
        return view('offboarding.index', ['cases' => OffboardingCase::with(['employee', 'owner', 'tasks'])->orderByRaw("CASE status WHEN 'open' THEN 1 ELSE 2 END")->orderBy('last_day')->get(), 'employees' => User::whereIn('employment_status', ['active', 'leave'])->orderBy('first_name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['user_id' => ['required', Rule::exists('users', 'id')], 'last_day' => ['required', 'date', 'after_or_equal:today'], 'reason' => ['required', Rule::in(['resignation', 'termination', 'retirement', 'contract_end', 'other'])], 'risk_level' => ['required', Rule::in(['standard', 'elevated', 'critical'])], 'notes' => ['nullable', 'string', 'max:3000']]);
        $case = DB::transaction(function () use ($request, $data) {
            $case = OffboardingCase::create([...$data, 'owner_id' => auth('admin')->id()]);
            foreach ([['Revocar accesos y sesiones', 'security'], ['Recuperar equipos y activos', 'assets'], ['Transferir conocimiento y archivos', 'knowledge'], ['Liquidar obligaciones y documentos', 'payroll'], ['Realizar entrevista de salida', 'people']] as [$title,$category]) {
                $case->tasks()->create(['title' => $title, 'category' => $category, 'due_date' => $case->last_day]);
            }AuditLog::record($request, 'offboarding.started', $case, [], ['employee_id' => $case->user_id, 'last_day' => $case->last_day]);

            return $case;
        });

        return back()->with('success', 'Offboarding iniciado con cinco controles obligatorios.');
    }

    public function task(Request $request, OffboardingTask $task, AccessLifecycleService $access)
    {
        $task->load('case');
        abort_unless($task->case, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'completed'])]]);
        $task->update(['status' => $data['status'], 'completed_at' => $data['status'] === 'completed' ? now() : null]);
        $case = $task->case;
        if ($case->tasks()->where('status', '!=', 'completed')->doesntExist()) {
            $case->update(['status' => 'completed', 'completed_at' => now()]);
            $case->employee->update(['employment_status' => 'inactive']);
            $access->revokeEmployee($case->employee);
        }AuditLog::record($request, 'offboarding.task_updated', $task, [], ['status' => $data['status']]);

        return back()->with('success', 'Control de salida actualizado.');
    }
}
