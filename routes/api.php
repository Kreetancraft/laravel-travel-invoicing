<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kreetancraft\TravelInvoicing\Http\Controllers\Api\InvoiceApiController;
use Kreetancraft\TravelInvoicing\Http\Controllers\Api\QuoteApiController;

/*
|--------------------------------------------------------------------------
| Travel Invoicing API Routes
|--------------------------------------------------------------------------
|
| All of this is staff work and none of it was protected. `store` created
| invoices and quotes, `destroy` deleted them, `index` listed every one with
| buyer names and amounts, and `invoices/{invoice}/payments` credited an invoice
| — all reachable by anyone who found the URL, because the group carried only
| the `api` middleware and every form request answered `authorize(): true`.
|
| The customer-facing side is not here. It lives in routes/public.php and is
| reached by an unguessable per-document token, which is what lets a buyer open
| their own invoice without an account and without being able to enumerate
| anyone else's.
|
| Recording a payment is kept here reluctantly. The same action is available on
| the invoice screen, authorized against the invoice, which is where the
| payment-gateway package deliberately leaves refunds rather than exposing a
| route for them. If you do not need it over HTTP, remove it.
|
*/

$protected = config('travel-invoicing.routes.protected_middleware', ['auth']);

Route::get('/health', fn () => response()->json(['status' => 'ok', 'module' => 'travel-invoicing']));

Route::middleware($protected)->group(function (): void {
    Route::apiResource('quotes', QuoteApiController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('invoices', InvoiceApiController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::post('invoices/{invoice}/payments', [InvoiceApiController::class, 'recordPayment'])
        ->name('invoices.payments.store');
});
