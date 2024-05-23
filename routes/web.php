<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginAdminController;
use App\Http\Controllers\LogoutAdminController;
use App\Http\Controllers\AdminHomeController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [RegisterController::class, 'show']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [LoginController::class, 'show']);
Route::post('/login', [LoginController::class, 'login']);
Route::get('/logout', [LogoutController::class, 'logout']);
Route::get('/home', [HomeController::class, 'index']);
Route::resource('empleados', EmpleadoController::class)->except(['destroy']);
Route::delete('empleados/{user}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
Route::resource('departamentos', DepartamentoController::class);
Route::get('/departamentos', [DepartamentoController::class, 'index'])->name('departamentos.index');
Route::get('admin/register', [AdminController::class, 'show'])->name('admin.register');
Route::post('admin/register', [AdminController::class, 'register']);
Route::get('admin/login', [LoginAdminController::class, 'showAdmin'])->name('admin.login');
Route::post('admin/login', [LoginAdminController::class, 'loginAdmin']);
Route::get('/admin/logout', [LogoutAdminController::class, 'logoutAdmin'])->name('admin.logout');
Route::post('/admin/logout', [LogoutAdminController::class, 'logoutAdmin'])->name('admin.logout');
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/home', [AdminHomeController::class, 'index'])->name('admin.home');
});


