<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LogoutAdminController extends Controller
    {
        public function logoutAdmin(){
        // Cierra la sesión del usuario administrador
        Auth::guard('admin')->logout();

        // Redirige al usuario a la página de inicio de sesión
        return redirect()->route('admin.login');
    }
}

