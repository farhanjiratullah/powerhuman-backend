<?php

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('guest')->group(function () {
//     Route::post('register', [RegisteredUserController::class, 'store']);

//     Route::post('login', [AuthenticatedSessionController::class, 'store']);
// });

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return ResponseFormatter::success($request->user(), 'Successfully fetched logged in user');
//     });

//     Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
//         ->name('logout');
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('company/{company}/teams', [TeamController::class, 'getAllTeamsBasedOnCompanyId'])->name('teams.company.show');
    Route::get('company/{company}/roles', [RoleController::class, 'getAllRolesBasedOnCompanyId'])->name('roles.company.show');

    Route::get('companies/all', [CompanyController::class, 'all'])->name('companies.all');
    Route::apiResource('companies', CompanyController::class)->except('destroy');

    Route::get('teams/all', [TeamController::class, 'all'])->name('teams.all');

    Route::get('roles/all', [RoleController::class, 'all'])->name('roles.all');


    Route::apiResources([
        'teams' => TeamController::class,
        'roles' => RoleController::class,
        'employees' => EmployeeController::class
    ]);
});

require __DIR__ . '/auth.php';
