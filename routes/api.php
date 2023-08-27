<?php

use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\TeamController;
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

require __DIR__ . '/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::get('companies/all', [CompanyController::class, 'all'])->name('companies.all');
    Route::apiResource('companies', CompanyController::class)->except('destroy');

    Route::get('teams/all', [TeamController::class, 'all'])->name('teams.all');
    Route::get('teams/company/{company}', [TeamController::class, 'getAllTeamsBasedOnCompanyId'])->name('teams.company.show');

    Route::get('roles/all', [RoleController::class, 'all'])->name('roles.all');
    Route::get('roles/company/{company}', [RoleController::class, 'getAllRolesBasedOnCompanyId'])->name('roles.company.show');

    Route::apiResources([
        'teams' => TeamController::class,
        'roles' => RoleController::class,
        'employees' => EmployeeController::class
    ]);
});
