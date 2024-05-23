<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhereHas('departamento', function ($query) use ($search) {
                      $query->where('nombre', 'like', '%' . $search . '%');
                  });
        }

        $empleados = $query->paginate(10);
        return view('empleados.index', compact('empleados'));
    }

    public function create()
    {
        $departamentos = Departamento::all();
        return view('empleados.create', compact('departamentos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|unique:users,username|max:255',
            'departamento_id' => 'required|exists:departamentos,id',
            'password' => 'required|min:8|confirmed',
        ]);

        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'departamento_id' => $request->departamento_id,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('empleados.index')->with('success', 'Empleado creado exitosamente.');
    }

    public function edit(User $empleado)
    {
        $departamentos = Departamento::all();
        return view('empleados.edit', compact('empleado', 'departamentos'));
    }

    public function update(Request $request, User $empleado)
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,' . $empleado->id,
            'username' => 'required|unique:users,username,' . $empleado->id,
            'departamento_id' => 'required|exists:departamentos,id',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'username', 'departamento_id']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $empleado->update($data);
        return redirect()->route('empleados.index')->with('success', 'Empleado actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        $existingUser = User::find($user->id);

        if (!$existingUser) {
            return redirect()->route('empleados.index')->with('error', 'Empleado no encontrado.');
        }

        $existingUser->delete();

        return redirect()->route('empleados.index')->with('success', 'Empleado eliminado exitosamente.');
    }
}
