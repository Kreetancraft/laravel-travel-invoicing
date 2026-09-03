<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\IssueInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\VoidInvoiceAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;

it('creates an invoice and tracks partial payments toward full settlement', function () {
    $create = app(CreateInvoiceAction::class);
    $issue = app(IssueInvoiceAction::class);
    $recordPayment = app(RecordInvoicePaymentAction::class);

    $invoice = $create->handle([
        'buyer_name' => 'Pam Beesly',
        'buyer_email' => 'pam@dundermifflin.com',
        'title' => 'Annapurna Sanctuary Trek',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
    ], [
        ['description' => 'Trek Guide & Porter', 'quantity' => 1, 'unit_price_cents' => 100000],
        ['description' => 'TIMS & National Park Permit', 'quantity' => 1, 'unit_price_cents' => 50000],
    ]);

    expect($invoice->invoice_number)->toStartWith('INV-')
        ->and($invoice->grand_total_cents)->toBe(150000)
        ->and($invoice->amount_paid_cents)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft);

    $invoice = $issue->handle($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::Issued);

    // Record partial deposit payment ($500)
    // recordPayment takes positional arguments and returns the InvoicePayment,
    // not the invoice — see InvoicesContract::recordPayment.
    $recordPayment->handle($invoice, 50000, 'stripe', 'pi_test_12345');
    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(50000)
        ->and($invoice->balanceDueCents())->toBe(100000)
        ->and($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->payments)->toHaveCount(1);

    // Settle remaining balance ($1000)
    $recordPayment->handle($invoice, 100000, 'bank_transfer', 'WIRE-998877');
    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(150000)
        ->and($invoice->balanceDueCents())->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull()
        ->and($invoice->payments)->toHaveCount(2);
});

it('marks an invoice as void', function () {
    $create = app(CreateInvoiceAction::class);
    $void = app(VoidInvoiceAction::class);

    $invoice = $create->handle([
        'buyer_name' => 'Ryan Howard',
        'buyer_email' => 'ryan@wuphf.com',
    ], [
        ['description' => 'Consulting', 'quantity' => 1, 'unit_price_cents' => 20000],
    ]);

    $invoice = $void->handle($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::Void);
});
