<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Companies\CompanyController;
use App\Http\Controllers\Api\Customers\CustomerController;
use App\Http\Controllers\Api\Invoicing\CreditNoteController;
use App\Http\Controllers\Api\Invoicing\DebitNoteController;
use App\Http\Controllers\Api\Invoicing\InvoiceController;
use App\Http\Controllers\Api\Invoicing\ResolutionController;
use App\Http\Controllers\Api\Users\UserController;

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
    Route::post('invoices/{id}/change-status', [InvoiceController::class, 'changeStatus']);
    Route::get('invoices/{id}/xml', [InvoiceController::class, 'generateXml']);
    Route::get('invoices/{id}/pdf', [InvoiceController::class, 'generatePdf']);

    // Notas Crédito
    Route::apiResource('credit-notes', CreditNoteController::class);
    Route::post('credit-notes/{id}/change-status', [CreditNoteController::class, 'changeStatus']);
    Route::get('credit-notes/{id}/xml', [CreditNoteController::class, 'generateXml']);
    Route::get('credit-notes/{creditNote}/pdf', [CreditNoteController::class, 'pdf']);

    // Notas Débito
    Route::apiResource('debit-notes', DebitNoteController::class);
    Route::post('debit-notes/{id}/change-status', [DebitNoteController::class, 'changeStatus']);
    Route::get('debit-notes/{id}/xml', [DebitNoteController::class, 'generateXml']);
    Route::get('debit-notes/{debitNote}/pdf', [DebitNoteController::class, 'pdf']);

});
