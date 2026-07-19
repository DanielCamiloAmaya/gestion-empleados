<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\AuditLog;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartamentoController extends Controller
{
    public function index(Request $request)
    {
        $departamentos = Departamento::query()
            ->withCount(['empleados' => fn ($query) => $query->whereIn('employment_status', ['active', 'onboarding', 'leave'])])
            ->when($request->filled('search'), fn ($query) => $query->where('nombre', 'like', '%'.trim($request->input('search')).'%'))
            ->orderByDesc('is_active')->orderBy('nombre')->paginate(12)->withQueryString();

        return view('departamentos.index', compact('departamentos'));
    }

    public function create()
    {
        return view('departamentos.create');
    }

    public function store(DepartmentRequest $request)
    {
        $department = DB::transaction(function () use ($request) {
            $department = Departamento::create($request->validated());
            AuditLog::record($request, 'department.created', $department, [], $department->toArray());

            return $department;
        });

        return redirect()->route('departamentos.index')->with('success', "Departamento {$department->nombre} creado.");
    }

    public function edit(Departamento $departamento)
    {
        return view('departamentos.edit', compact('departamento'));
    }

    public function update(DepartmentRequest $request, Departamento $departamento)
    {
        DB::transaction(function () use ($request, $departamento) {
            $old = $departamento->toArray();
            $departamento->update($request->validated());
            AuditLog::record($request, 'department.updated', $departamento, $old, $departamento->fresh()->toArray());
        });

        return redirect()->route('departamentos.index')->with('success', 'Departamento actualizado.');
    }

    public function destroy(Request $request, Departamento $departamento)
    {
        if ($departamento->empleados()->exists()) {
            return back()->with('error', 'No se puede eliminar un departamento con empleados asociados. Reubícalos primero.');
        }

        DB::transaction(function () use ($request, $departamento) {
            $old = $departamento->toArray();
            AuditLog::record($request, 'department.deleted', $departamento, $old);
            $departamento->delete();
        });

        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado.');
    }
}
