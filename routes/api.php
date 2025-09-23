<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PeopleController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PeopleCertificateController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes with token authentication
Route::middleware(['api.token'])->group(function () {
    
// People API Routes
Route::prefix('people')->group(function () {
    Route::get('/compact', [PeopleController::class, 'compact']); // Компактное API для бота
    Route::get('/', [PeopleController::class, 'index']);
    Route::post('/', [PeopleController::class, 'store']);
    Route::post('/bulk', [PeopleController::class, 'storeBulk']);
    Route::get('/{id}', [PeopleController::class, 'show']);
    Route::put('/{id}', [PeopleController::class, 'update']);
    Route::delete('/{id}', [PeopleController::class, 'destroy']);
});

// Certificates API Routes
Route::prefix('certificates')->group(function () {
    Route::get('/', [CertificateController::class, 'index']);
    Route::post('/', [CertificateController::class, 'store']);
    Route::get('/{id}', [CertificateController::class, 'show']);
    Route::put('/{id}', [CertificateController::class, 'update']);
    Route::delete('/{id}', [CertificateController::class, 'destroy']);
});

// People Certificates API Routes
Route::prefix('people-certificates')->group(function () {
    Route::post('/', [PeopleCertificateController::class, 'store']);
    Route::put('/{id}', [PeopleCertificateController::class, 'update']);
    Route::delete('/{id}', [PeopleCertificateController::class, 'destroy']);
});

// Reports API Routes
Route::prefix('reports')->group(function () {
    Route::get('/expired-certificates', [ReportController::class, 'expiredCertificates']);
    Route::get('/expiring-soon', [ReportController::class, 'expiringSoon']);
    Route::get('/people-status', [ReportController::class, 'peopleStatus']);
});

}); // End of api.token middleware group