<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiTokenController;

Route::get('/', function () {
    return view('welcome');
});

// Маршруты аутентификации
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Маршруты для охраны труда (требуют аутентификации)
Route::middleware(['auth'])->group(function () {
    Route::get('/safety', [SafetyController::class, 'index'])->name('safety.index');
Route::post('/safety/update-certificate/{peopleId}/{certificateId}', [SafetyController::class, 'updateCertificate'])->name('safety.update-certificate');
Route::post('/safety/store-person', [SafetyController::class, 'storePerson'])->name('safety.store-person');
Route::post('/safety/update-person/{id}', [SafetyController::class, 'updatePerson'])->name('safety.update-person');
Route::post('/safety/store-certificate', [SafetyController::class, 'storeCertificate'])->name('safety.store-certificate');
Route::get('/safety/photo/{filename}', [SafetyController::class, 'showPhoto'])->name('safety.photo');
Route::get('/safety/passport/{filename}', [SafetyController::class, 'downloadPassport'])->name('safety.passport');
Route::get('/safety/certificate-file/{peopleId}/{certificateId}', [SafetyController::class, 'downloadCertificateFile'])->name('safety.certificate-file');
Route::get('/safety/certificate-view/{peopleId}/{certificateId}', [SafetyController::class, 'viewCertificateFile'])->name('safety.certificate-view');
Route::get('/safety/certificates-file/{filename}', [SafetyController::class, 'downloadCertificatesFile'])->name('safety.certificates-file');
Route::get('/safety/certificate/{id}/description', [SafetyController::class, 'getCertificateDescription'])->name('safety.certificate-description');
Route::delete('/safety/delete-person/{id}', [SafetyController::class, 'deletePerson'])->name('safety.delete-person');
Route::delete('/safety/delete-certificate/{id}', [SafetyController::class, 'deleteCertificate'])->name('safety.delete-certificate');
Route::delete('/safety/delete-person-certificate/{peopleId}/{certificateId}', [SafetyController::class, 'deletePersonCertificate'])->name('safety.delete-person-certificate');
Route::post('/safety/update-certificate-info/{id}', [SafetyController::class, 'updateCertificateInfo'])->name('safety.update-certificate-info');

    // Маршруты для управления порядком сертификатов
    Route::get('/safety/certificate-order-modal', [SafetyController::class, 'showCertificateOrderModal'])->name('safety.certificate-order-modal');
    Route::post('/safety/update-certificate-order', [SafetyController::class, 'updateCertificateOrder'])->name('safety.update-certificate-order');

    // Маршруты для управления API токенами
    Route::resource('api-tokens', ApiTokenController::class);
    Route::post('/api-tokens/{apiToken}/activate', [ApiTokenController::class, 'activate'])->name('api-tokens.activate');
    Route::post('/api-tokens/{apiToken}/deactivate', [ApiTokenController::class, 'deactivate'])->name('api-tokens.deactivate');
    
    // Документация API
    Route::get('/api-docs', function() {
        return view('api-docs');
    })->name('api-docs');
    
    // Примеры использования API
    Route::get('/api-examples', function() {
        return view('api-examples');
    })->name('api-examples');
});
