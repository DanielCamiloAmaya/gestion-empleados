<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function show()
    {
        return view('admin.register');
    }

    public function register(AdminRequest $request)
    {
        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect('/admin/login')->with('success', 'Admin registrado correctamente.');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Autenticación exitosa para admins
            return redirect()->intended('/admin/login');
        } else {
            // Autenticación fallida para admins
            return back()->withErrors(['email' => 'Credenciales incorrectas']);
        }
    }
}





