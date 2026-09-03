<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Facades\TravelInvoicing;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;

/**
 * The facade's advertised API.
 *
 * Its docblock promised createQuote(), createInvoice() and getSettings() while
 * it resolved to QuotesContract, which has none of them. Every documented call
 * threw BadMethodCallException, and the methods that did work were undocumented
 * — so the docblock was the one reliable way to fail.
 */
it('creates a quote through the facade', function (): void {
    $quote = TravelInvoicing::createQuote([
        'buyer_name' => 'Holly Flax',
        'buyer_email' => 'holly@example.com',
        'title' => 'Langtang Trek',
        'valid_until' => now()->addDays(14)->toDateString(),
    ], [
        ['title' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 80000],
    ]);

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and($quote->grand_total_cents)->toBe(80000);
});

it('creates an invoice through the facade', function (): void {
    $invoice = TravelInvoicing::createInvoice([
        'buyer_name' => 'Holly Flax',
        'buyer_email' => 'holly@example.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 60000],
    ]);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->grand_total_cents)->toBe(60000);
});

it('reads and writes settings through the facade', function (): void {
    TravelInvoicing::updateSettings(['business_name' => 'Kreetancraft Expeditions']);

    expect(TravelInvoicing::getSettings()->business_name)->toBe('Kreetancraft Expeditions');
});

it('converts a quote through the facade', function (): void {
    $quote = TravelInvoicing::createQuote([
        'buyer_name' => 'Holly Flax',
        'buyer_email' => 'holly@example.com',
        'title' => 'Langtang Trek',
        'valid_until' => now()->addDays(14)->toDateString(),
    ], [
        ['title' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 80000],
    ]);

    $invoice = TravelInvoicing::convertQuote($quote);

    expect($invoice->quote_id)->toBe($quote->id);
});

it('records a payment through the facade', function (): void {
    $invoice = TravelInvoicing::createInvoice([
        'buyer_name' => 'Holly Flax',
        'buyer_email' => 'holly@example.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => 60000],
    ]);

    TravelInvoicing::recordPayment($invoice, 60000, 'cash', 'FACADE-1');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('still reaches the contracts for anything else', function (): void {
    expect(TravelInvoicing::quotes())->toBeInstanceOf(QuotesContract::class)
        ->and(TravelInvoicing::invoices())->toBeInstanceOf(InvoicesContract::class);
});
