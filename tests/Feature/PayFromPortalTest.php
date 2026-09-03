<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Support\CheckoutLink;

/**
 * Paying from the portal.
 *
 * The page is called `invoice-pay` and there was no way to pay from it. A
 * customer accepted a quote, an invoice appeared, and the trail went cold —
 * totals, and bank wire text to act on by hand.
 *
 * This package does not take payments and should not learn how, so it asks the
 * host for a URL and puts a button on it.
 */
function payableInvoice(int $totalCents = 100000, int $depositCents = 20000): Invoice
{
    $invoice = app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Erin Hannon',
        'buyer_email' => 'erin@example.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
        'deposit_amount_cents' => $depositCents,
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => $totalCents],
    ]);

    return $invoice->fresh();
}

function wireCheckout(): void
{
    config()->set(
        'travel-invoicing.checkout_url',
        fn (Invoice $invoice, string $portion): string => "https://pay.example/checkout?invoice={$invoice->getKey()}&amount={$portion}"
    );
}

it('shows no pay buttons when the host takes no online payments', function (): void {
    config()->set('travel-invoicing.checkout_url', null);

    $invoice = payableInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}")
        ->assertOk()
        ->assertDontSee('Pay online');
});

it('offers the deposit and the full balance separately', function (): void {
    // The question this exists to answer: having accepted, how does someone pay
    // their 20%?
    wireCheckout();

    $invoice = payableInvoice(100000, 20000);

    $this->get("/portal/invoices/{$invoice->public_token}")
        ->assertOk()
        ->assertSee('Pay online')
        ->assertSee('Pay deposit')
        ->assertSee('Pay in full');
});

it('asks for the deposit amount, not the whole invoice', function (): void {
    $invoice = payableInvoice(100000, 20000);

    expect(CheckoutLink::amountCentsFor($invoice, CheckoutLink::DEPOSIT))->toBe(20000)
        ->and(CheckoutLink::amountCentsFor($invoice, CheckoutLink::BALANCE))->toBe(100000);
});

it('asks only for what is left of the deposit', function (): void {
    // Somebody who paid part of their deposit owes the rest, not all of it again.
    $invoice = payableInvoice(100000, 20000);
    app(RecordInvoicePaymentAction::class)->handle($invoice, 5000, 'cash', 'part');

    expect(CheckoutLink::amountCentsFor($invoice->fresh(), CheckoutLink::DEPOSIT))->toBe(15000);
});

it('stops offering the deposit once it is covered', function (): void {
    // Otherwise "pay the deposit" and "pay the balance" become the same button.
    wireCheckout();

    $invoice = payableInvoice(100000, 20000);
    app(RecordInvoicePaymentAction::class)->handle($invoice, 20000, 'cash', 'deposit-paid');

    $fresh = $invoice->fresh();

    expect(CheckoutLink::offersDeposit($fresh))->toBeFalse();

    $this->get("/portal/invoices/{$fresh->public_token}")
        ->assertOk()
        ->assertDontSee('Pay deposit')
        ->assertSee('Pay online');
});

it('offers nothing to pay once the invoice is settled', function (): void {
    wireCheckout();

    $invoice = payableInvoice(50000, 10000);
    app(RecordInvoicePaymentAction::class)->handle($invoice, 50000, 'cash', 'settled');

    $fresh = $invoice->fresh();

    expect(CheckoutLink::for($fresh, CheckoutLink::BALANCE))->toBeNull();

    $this->get("/portal/invoices/{$fresh->public_token}")
        ->assertOk()
        ->assertDontSee('Pay online');
});

it('keeps the invoice readable when the checkout link is misconfigured', function (): void {
    // A broken link must not take the page down. The customer still sees what
    // they owe and how to pay by transfer.
    config()->set('travel-invoicing.checkout_url', function (): string {
        throw new RuntimeException('route not defined');
    });

    $invoice = payableInvoice();

    $this->get("/portal/invoices/{$invoice->public_token}")
        ->assertOk()
        ->assertSee($invoice->invoice_number);
});

it('hands the host the invoice and the portion it asked about', function (): void {
    wireCheckout();

    $invoice = payableInvoice();

    expect(CheckoutLink::for($invoice, CheckoutLink::DEPOSIT))
        ->toBe("https://pay.example/checkout?invoice={$invoice->getKey()}&amount=deposit")
        ->and(CheckoutLink::for($invoice, CheckoutLink::BALANCE))
        ->toBe("https://pay.example/checkout?invoice={$invoice->getKey()}&amount=balance");
});
