<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\CreateInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\EditInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ManageInvoices;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ShowInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\CreateQuote;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\EditQuote;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\ManageQuotes;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\ShowQuote;
use Kreetancraft\TravelInvoicing\Livewire\Settings\ManageInvoicingSettings;

Route::prefix('quotes')->group(function (): void {
    Route::get('/', ManageQuotes::class)->name(config('travel-invoicing.routes.names.quotes', 'admin.quotes'));
    Route::get('/create', CreateQuote::class)->name(config('travel-invoicing.routes.names.quotes.create', 'admin.quotes.create'));
    Route::get('/{quote}', ShowQuote::class)->name(config('travel-invoicing.routes.names.quotes.show', 'admin.quotes.show'));
    Route::get('/{quote}/edit', EditQuote::class)->name(config('travel-invoicing.routes.names.quotes.edit', 'admin.quotes.edit'));
});

Route::prefix('invoices')->group(function (): void {
    Route::get('/', ManageInvoices::class)->name(config('travel-invoicing.routes.names.invoices', 'admin.invoices'));
    Route::get('/create', CreateInvoice::class)->name(config('travel-invoicing.routes.names.invoices.create', 'admin.invoices.create'));
    Route::get('/{invoice}', ShowInvoice::class)->name(config('travel-invoicing.routes.names.invoices.show', 'admin.invoices.show'));
    Route::get('/{invoice}/edit', EditInvoice::class)->name(config('travel-invoicing.routes.names.invoices.edit', 'admin.invoices.edit'));
});

Route::get('invoicing/settings', ManageInvoicingSettings::class)
    ->name(config('travel-invoicing.routes.names.settings', 'admin.invoicing.settings'));
