<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Livewire\Invoices\ShowInvoice;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Livewire\Livewire;

/**
 * Recording a payment an admin took by hand.
 *
 * The form asked for cents, so entering $3,240.00 meant typing 324000 — mental
 * arithmetic, and a number three orders of magnitude larger than the one printed
 * on the invoice. One slip records a payment a hundred times too big, and the
 * only guard was `min:1`.
 */
function screenInvoice(int $totalCents = 100000): Invoice
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Bob Vance',
        'buyer_email' => 'bob@vancerefrigeration.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => $totalCents],
    ]);
}

it('offers the outstanding balance in the invoice currency, not cents', function (): void {
    $invoice = screenInvoice(100000);

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->assertSet('paymentAmount', '1000.00');
});

it('records what the admin typed, read as currency', function (): void {
    $invoice = screenInvoice(100000);

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', '250.50')
        ->set('paymentGateway', 'cash')
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect($invoice->fresh()->amount_paid_cents)->toBe(25050);
});

it('refuses to take more than is outstanding', function (): void {
    // An invoice cannot be overpaid. The old rule was `min:1` and nothing else.
    $invoice = screenInvoice(100000);

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', '5000.00')
        ->call('recordPayment')
        ->assertHasErrors('paymentAmount');

    expect($invoice->fresh()->amount_paid_cents)->toBe(0);
});

it('refuses zero and negatives', function (string $amount): void {
    $invoice = screenInvoice();

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', $amount)
        ->call('recordPayment')
        ->assertHasErrors('paymentAmount');
})->with(['zero' => '0', 'negative' => '-50']);

it('will not let a gateway payment be invented by hand', function (): void {
    // Stripe and the bank record themselves when their webhook lands. Choosing
    // one here would create a payment with no gateway reference, which the real
    // webhook then records again — the same money counted twice.
    $invoice = screenInvoice();

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentGateway', 'stripe')
        ->set('paymentAmount', '100.00')
        ->call('recordPayment')
        ->assertHasErrors('paymentGateway');

    expect($invoice->fresh()->amount_paid_cents)->toBe(0);
});

it('generates a reference so the admin does not invent one', function (): void {
    $invoice = screenInvoice();

    $component = Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentGateway', 'cash')
        ->call('openRecordPaymentModal');

    expect($component->get('paymentReference'))->toStartWith('CASH-');
});

it('names the reference after how the money arrived', function (string $method, string $prefix): void {
    $invoice = screenInvoice();

    $component = Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentGateway', $method)
        ->call('openRecordPaymentModal');

    expect($component->get('paymentReference'))->toStartWith($prefix);
})->with([
    'wire' => ['bank_transfer', 'WIRE-'],
    'cash' => ['cash', 'CASH-'],
    'cheque' => ['cheque', 'CHQ-'],
]);

it('still records a reference when the admin clears the field', function (): void {
    // Recording is idempotent on the reference, so a payment without one cannot
    // be recognised as a repeat if the form is submitted twice.
    $invoice = screenInvoice();

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', '100.00')
        ->set('paymentReference', '')
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect($invoice->fresh()->payments->first()->transaction_reference)->not->toBeEmpty();
});

it('offers the new balance after a part payment', function (): void {
    // Reopening the modal used to suggest the amount just paid.
    $invoice = screenInvoice(100000);

    $component = Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', '400.00')
        ->call('recordPayment');

    expect($component->get('paymentAmount'))->toBe('600.00');
});

it('settles the invoice when the last of it is paid', function (): void {
    $invoice = screenInvoice(100000);

    Livewire::test(ShowInvoice::class, ['invoice' => $invoice])
        ->set('paymentAmount', '1000.00')
        ->call('recordPayment');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});
