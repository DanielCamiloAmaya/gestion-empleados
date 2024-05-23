<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginAdminRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginAdminController extends Controller
{
    public function showAdmin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.home');
        }
        return view('admin.login');
    }

    public function loginAdmin(LoginAdminRequest $request)
    {
        $credentials = $request->getCredentials();

        if (!Auth::guard('admin')->validate($credentials)) {
            return redirect()->route('admin.login')->withErrors('Nombre y/o contraseña es incorrecta');
        }

        $admin = Auth::guard('admin')->getProvider()->retrieveByCredentials($credentials);

        Auth::guard('admin')->login($admin);

        return $this->authenticated($request, $admin);
    }

    protected function authenticated(Request $request, $admin)
    {
        return redirect()->route('admin.home');
    }
}



