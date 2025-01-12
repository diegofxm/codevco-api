<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Companies\CompanyController;
use App\Http\Controllers\Api\Customers\CustomerController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Controllers\Api\Invoicing\ResolutionController;
use App\Http\Controllers\Api\Invoicing\InvoiceController;
use App\Http\Controllers\Api\Invoicing\CreditNoteController;
use App\Http\Controllers\Api\Invoicing\DebitNoteController;

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Rutas de autenticación que requieren estar autenticado
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // CRUD de usuarios
    Route::apiResource('users', UserController::class);

    // CRUD de empresas
    Route::apiResource('companies', CompanyController::class);

    // CRUD de clientes
    Route::apiResource('customers', CustomerController::class);

    // CRUD de resoluciones
    Route::apiResource('resolutions', ResolutionController::class);

    // Rutas de facturación
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('credit-notes', CreditNoteController::class);
    Route::apiResource('debit-notes', DebitNoteController::class);

});
