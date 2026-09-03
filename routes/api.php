<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kreetancraft\TravelInvoicing\Http\Controllers\Api\InvoiceApiController;
use Kreetancraft\TravelInvoicing\Http\Controllers\Api\QuoteApiController;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'module' => 'travel-invoicing']));

Route::apiResource('quotes', QuoteApiController::class)->only(['index', 'store', 'show', 'destroy']);
Route::apiResource('invoices', InvoiceApiController::class)->only(['index', 'store', 'show', 'destroy']);
Route::post('invoices/{invoice}/payments', [InvoiceApiController::class, 'recordPayment'])->name('invoices.payments.store');
