<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Livewire\Invoices\CreateInvoice;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ManageInvoices;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\CreateQuote;
use Kreetancraft\TravelInvoicing\Livewire\Quotes\ManageQuotes;
use Kreetancraft\TravelInvoicing\Livewire\Settings\ManageInvoicingSettings;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Livewire\Livewire;

it('renders manage quotes and create quote livewire components', function () {
    Livewire::test(ManageQuotes::class)
        ->assertSuccessful()
        ->assertSee('Commercial Quotes & Proposals');

    Livewire::test(CreateQuote::class)
        ->assertSuccessful()
        ->assertSee('Create Commercial Quote / Proposal')
        ->set('buyer_name', 'Andy Bernard')
        ->set('buyer_email', 'andy@cornell.edu')
        ->set('title', 'Langtang Trek')
        ->set('valid_until', now()->addDays(14)->toDateString())
        ->set('items', [
            ['title' => 'Trek Package', 'description' => 'Guide + Porter', 'quantity' => 1, 'unit_price_cents' => 90000],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Quote::where('buyer_email', 'andy@cornell.edu')->exists())->toBeTrue();
});

it('renders manage invoices, create invoice, and settings components', function () {
    Livewire::test(ManageInvoices::class)
        ->assertSuccessful()
        ->assertSee('Invoices & Billing');

    Livewire::test(CreateInvoice::class)
        ->assertSuccessful()
        ->assertSee('Create Tax Invoice')
        ->set('buyer_name', 'Stanley Hudson')
        ->set('buyer_email', 'stanley@crosswords.com')
        ->set('issue_date', now()->toDateString())
        ->set('items', [
            ['description' => 'Everest Flight Tour', 'quantity' => 1, 'unit_price_cents' => 25000],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Invoice::where('buyer_email', 'stanley@crosswords.com')->exists())->toBeTrue();

    Livewire::test(ManageInvoicingSettings::class)
        ->assertSuccessful()
        ->assertSee('Invoicing & Billing Settings')
        ->set('business_name', 'Himalayan Trek Updated Ltd')
        ->call('save')
        ->assertHasNoErrors();
});
