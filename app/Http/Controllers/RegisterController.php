<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Departamento;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function show()
    {
        $departamentos = Departamento::all();
        return view('auth.register', compact('departamentos'));
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        return redirect('/login')->with('success', 'Cuenta creada exitosamente.');
    }
}


