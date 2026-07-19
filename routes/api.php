<?php

use App\Http\Controllers\Api\EmployeeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/employees', [EmployeeApiController::class, 'index'])->middleware(['api.token:employees:read', 'plan:api']);
});
Route::prefix('scim/v2')->middleware(['api.token:scim', 'plan:scim'])->group(function () {
    Route::get('/Users', [EmployeeApiController::class, 'scimIndex'])->name('api.scim.users.index');
    Route::post('/Users', [EmployeeApiController::class, 'scimStore'])->name('api.scim.users.store');
    Route::get('/Users/{user}', [EmployeeApiController::class, 'scimShow'])->name('api.scim.users.show');
    Route::patch('/Users/{user}', [EmployeeApiController::class, 'scimPatch'])->name('api.scim.users.patch');
});
