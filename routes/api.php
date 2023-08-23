<?php

use App\Http\Controllers\API\CompanyController;
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

    Route::apiResources([
        'teams' => TeamController::class
    ]);
});
