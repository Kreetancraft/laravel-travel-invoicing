<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Events\InvoicePaid;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;

/**
 * Recording money against an invoice.
 *
 * This is where the payment package will hand off, so it has to survive being
 * called more than once with the same payment. A gateway delivers a webhook more
 * than once as a matter of routine — that is how it guarantees delivery at all —
 * and the invoice must not be credited twice for money that arrived once.
 */
function invoiceFor(int $totalCents): Invoice
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Pam Beesly',
        'buyer_email' => 'pam@dundermifflin.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => $totalCents],
    ]);
}

it('records the same gateway payment only once', function (): void {
    // The webhook redelivery case, which is the normal case rather than the
    // exceptional one.
    $invoice = invoiceFor(150000);
    $action = app(RecordInvoicePaymentAction::class);

    $action->handle($invoice, 50000, 'stripe', 'pi_test_duplicate');
    $action->handle($invoice, 50000, 'stripe', 'pi_test_duplicate');

    $invoice->refresh();

    expect($invoice->payments)->toHaveCount(1)
        ->and($invoice->amount_paid_cents)->toBe(50000);
});

it('hands back the payment it already had rather than erroring', function (): void {
    $invoice = invoiceFor(150000);
    $action = app(RecordInvoicePaymentAction::class);

    $first = $action->handle($invoice, 50000, 'stripe', 'pi_same');
    $second = $action->handle($invoice, 50000, 'stripe', 'pi_same');

    expect($second->id)->toBe($first->id);
});

it('still records two genuinely different payments', function (): void {
    // A deposit and a balance are two payments, not a duplicate.
    $invoice = invoiceFor(150000);
    $action = app(RecordInvoicePaymentAction::class);

    $action->handle($invoice, 50000, 'stripe', 'pi_deposit');
    $action->handle($invoice, 100000, 'stripe', 'pi_balance');

    $invoice->refresh();

    expect($invoice->payments)->toHaveCount(2)
        ->and($invoice->amount_paid_cents)->toBe(150000)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid);
});

it('records manual payments that carry no reference', function (): void {
    // Cash and cheques have nothing to deduplicate on, so two of them are two
    // payments — the guard must not collapse them.
    $invoice = invoiceFor(150000);
    $action = app(RecordInvoicePaymentAction::class);

    $action->handle($invoice, 20000, 'cash');
    $action->handle($invoice, 20000, 'cash');

    $invoice->refresh();

    expect($invoice->payments)->toHaveCount(2)
        ->and($invoice->amount_paid_cents)->toBe(40000);
});

it('adds up the payments rather than trusting a running total', function (): void {
    // The total is recomputed from the rows, so a stale copy of the invoice
    // cannot make it drift. Writing a wrong total directly is corrected by the
    // next payment rather than compounded by it.
    $invoice = invoiceFor(150000);
    $action = app(RecordInvoicePaymentAction::class);

    $action->handle($invoice, 50000, 'stripe', 'pi_one');

    // Something else corrupts the cached column.
    $invoice->forceFill(['amount_paid_cents' => 999999])->save();

    $action->handle($invoice, 25000, 'stripe', 'pi_two');

    expect($invoice->fresh()->amount_paid_cents)->toBe(75000);
});

it('announces the invoice as paid exactly once', function (): void {
    Event::fake([InvoicePaid::class]);

    $invoice = invoiceFor(50000);
    $action = app(RecordInvoicePaymentAction::class);

    $action->handle($invoice, 50000, 'stripe', 'pi_settles');
    $action->handle($invoice, 50000, 'stripe', 'pi_settles');

    Event::assertDispatchedTimes(InvoicePaid::class, 1);
});

it('leaves an invoice partly paid when only a deposit has arrived', function (): void {
    $invoice = invoiceFor(150000);

    app(RecordInvoicePaymentAction::class)->handle($invoice, 30000, 'stripe', 'pi_deposit_only');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->balanceDueCents())->toBe(120000)
        ->and($invoice->paid_at)->toBeNull();
});

it('stores the payment against the invoice currency', function (): void {
    $invoice = invoiceFor(150000);

    $payment = app(RecordInvoicePaymentAction::class)->handle($invoice, 50000, 'stripe', 'pi_currency');

    expect($payment->currency)->toBe($invoice->currency)
        ->and(InvoicePayment::where('transaction_reference', 'pi_currency')->exists())->toBeTrue();
});
