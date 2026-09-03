<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kreetancraft\TravelInvoicing\Actions\Settings\GetInvoicingSettingsAction;
use Kreetancraft\TravelInvoicing\Http\Controllers\InvoicePdfController;
use Kreetancraft\TravelInvoicing\Http\Controllers\PublicInvoiceController;
use Kreetancraft\TravelInvoicing\Http\Controllers\PublicQuoteController;
use Kreetancraft\TravelInvoicing\Http\Controllers\QuotePdfController;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;

Route::prefix(config('travel-invoicing.routes.public_prefix', 'portal'))->group(function (): void {
    // Quote Proposal Review Portal
    Route::get('/quotes/{token}', function (string $token, GetInvoicingSettingsAction $getSettings) {
        $quote = Quote::with('items')->where('public_token', $token)->firstOrFail();

        return view('travel-invoicing::public.quote-review', [
            'quote' => $quote,
            'settings' => $getSettings->handle(),
        ]);
    })->name('travel-invoicing.public.quote');

    // Accept Quote from Portal (validates agreed_terms via controller)
    Route::post('/quotes/{token}/accept', [PublicQuoteController::class, 'accept'])->name('travel-invoicing.public.quote.accept');

    // Reject Quote from Portal
    Route::post('/quotes/{token}/reject', [PublicQuoteController::class, 'reject'])->name('travel-invoicing.public.quote.reject');

    // Quote PDF stream/view
    Route::get('/quotes/{token}/pdf', [QuotePdfController::class, 'show'])->name('travel-invoicing.pdf.quote');

    // Invoice View / Payment Portal
    Route::get('/invoices/{token}', [PublicInvoiceController::class, 'show'])->name('travel-invoicing.public.invoice');

    // Invoice PDF stream/view
    Route::get('/invoices/{token}/pdf', [InvoicePdfController::class, 'show'])->name('travel-invoicing.pdf.invoice');
});
