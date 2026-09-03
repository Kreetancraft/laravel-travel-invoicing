<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Quote\AcceptQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\ConvertQuoteToInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\CreateQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\RejectQuoteAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\SendQuoteAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Models\Quote;

it('creates a commercial quote proposal with itemized pricing and deposit', function () {
    $action = app(CreateQuoteAction::class);

    $quote = $action->handle([
        'buyer_name' => 'Michael Scott',
        'buyer_email' => 'michael@dundermifflin.com',
        'title' => 'Everest Base Camp 14-Day Private Expedition',
        'valid_until' => now()->addDays(14)->toDateString(),
        'deposit_percent' => 25,
        'discount_amount_cents' => 10000, // $100 discount
    ], [
        // Line items are the second argument, because they are rows in another
        // table rather than columns on this one.
        [
            'title' => 'Trek Package (Per Person)',
            'quantity' => 2,
            'unit_price_cents' => 150000, // $1500 each = $3000
        ],
        [
            'title' => 'Kathmandu-Lukla Helicopter Upgrade',
            'quantity' => 2,
            'unit_price_cents' => 40000, // $400 each = $800
        ],
    ]);

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and($quote->quote_reference)->toStartWith('QT-')
        ->and($quote->subtotal_cents)->toBe(380000) // $3800
        ->and($quote->discount_amount_cents)->toBe(10000)
        ->and($quote->grand_total_cents)->toBe(370000) // $3700
        ->and($quote->deposit_amount_cents)->toBe(92500) // 25% of $3700 = $925
        ->and($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->items)->toHaveCount(2);
});

it('sends, accepts, and rejects proposals through the state machine', function () {
    $create = app(CreateQuoteAction::class);
    $send = app(SendQuoteAction::class);
    $accept = app(AcceptQuoteAction::class);
    $reject = app(RejectQuoteAction::class);

    $quote = $create->handle([
        'buyer_name' => 'Dwight Schrute',
        'buyer_email' => 'dwight@schrutebeetfarms.com',
        'title' => 'Manaslu Circuit Trek',
        'valid_until' => now()->addDays(7)->toDateString(),
    ], [
        ['title' => 'Manaslu Package', 'quantity' => 1, 'unit_price_cents' => 120000],
    ]);

    expect($quote->status)->toBe(QuoteStatus::Draft);

    $quote = $send->handle($quote);
    expect($quote->status)->toBe(QuoteStatus::Sent)
        ->and($quote->sent_at)->not->toBeNull();

    // AcceptQuoteAction returns the invoice it generates, not the quote — so the
    // quote is re-read rather than overwritten with the return value.
    $accept->handle($quote);
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Accepted)
        ->and($quote->responded_at)->not->toBeNull();

    $reject->handle($quote, 'Client requested schedule changes');
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Rejected)
        ->and($quote->rejection_reason)->toBe('Client requested schedule changes');
});

it('converts an accepted proposal into a formal tax invoice', function () {
    $createQuote = app(CreateQuoteAction::class);
    $convert = app(ConvertQuoteToInvoiceAction::class);

    $quote = $createQuote->handle([
        'buyer_name' => 'Jim Halpert',
        'buyer_email' => 'jim@dundermifflin.com',
        'title' => 'Langtang Valley Trek',
        'valid_until' => now()->addDays(10)->toDateString(),
        'deposit_percent' => 20,
    ], [
        ['title' => 'Langtang Trek Package', 'quantity' => 2, 'unit_price_cents' => 80000],
    ]);

    $invoice = $convert->handle($quote);

    expect($invoice->invoice_number)->toStartWith('INV-')
        ->and($invoice->quote_id)->toBe($quote->id)
        ->and($invoice->grand_total_cents)->toBe(160000)
        ->and($invoice->deposit_amount_cents)->toBe(32000) // 20%
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->items)->toHaveCount(1)
        ->and($quote->fresh()->status)->toBe(QuoteStatus::Accepted);
});
