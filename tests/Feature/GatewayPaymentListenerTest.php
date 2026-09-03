<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Listeners\RecordGatewayPayment;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * The join between this package and a payment gateway.
 *
 * Neither package may depend on the other, so the listener reads the event by
 * duck typing and is subscribed by string class name. That means it can be
 * tested here with a stand-in: if this passes against an object shaped like the
 * real event, it passes against the real one.
 */
function paidInvoice(int $totalCents = 150000): Invoice
{
    return app(CreateInvoiceAction::class)->handle([
        'buyer_name' => 'Oscar Martinez',
        'buyer_email' => 'oscar@dundermifflin.com',
        'status' => InvoiceStatus::Issued,
        'issue_date' => now()->toDateString(),
    ], [
        ['description' => 'Trek package', 'quantity' => 1, 'unit_price_cents' => $totalCents],
    ]);
}

/**
 * Shaped like Kreetancraft\PaymentGateway\Models\Payment, without importing it.
 */
function gatewayPaymentFor(Invoice $invoice, int $amountCents, string $reference): object
{
    return new class($invoice, $amountCents, $reference)
    {
        public string $payable_type;

        public int $payable_id;

        public function __construct(Invoice $invoice, public int $amount_cents, public string $reference)
        {
            $this->payable_type = $invoice::class;
            $this->payable_id = (int) $invoice->getKey();
        }

        public string $gateway = 'stripe';
    };
}

function fireGatewayEvent(object $payment): void
{
    app(RecordGatewayPayment::class)->handle(new class($payment)
    {
        public function __construct(public object $payment) {}
    });
}

it('credits the invoice when the gateway reports a payment', function (): void {
    $invoice = paidInvoice();

    fireGatewayEvent(gatewayPaymentFor($invoice, 50000, 'PMT-260902-ABC123'));

    $invoice->refresh();

    expect($invoice->amount_paid_cents)->toBe(50000)
        ->and($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->payments)->toHaveCount(1);
});

it('settles the invoice when the payment covers it', function (): void {
    $invoice = paidInvoice(50000);

    fireGatewayEvent(gatewayPaymentFor($invoice, 50000, 'PMT-FULL'));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->balanceDueCents())->toBe(0);
});

it('credits it once when the webhook is delivered twice', function (): void {
    // The reason this listener leans on the action's idempotency rather than
    // adding its own: gateways redeliver, and that is by design.
    $invoice = paidInvoice();
    $payment = gatewayPaymentFor($invoice, 50000, 'PMT-REPEATED');

    fireGatewayEvent($payment);
    fireGatewayEvent($payment);

    $invoice->refresh();

    expect($invoice->payments)->toHaveCount(1)
        ->and($invoice->amount_paid_cents)->toBe(50000);
});

it('handles a deposit and then the balance', function (): void {
    // The flow this application actually needs.
    $invoice = paidInvoice(150000);

    fireGatewayEvent(gatewayPaymentFor($invoice, 30000, 'PMT-DEPOSIT'));
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);

    fireGatewayEvent(gatewayPaymentFor($invoice, 120000, 'PMT-BALANCE'));
    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->amount_paid_cents)->toBe(150000)
        ->and($invoice->payments)->toHaveCount(2);
});

it('ignores a payment for something that is not an invoice', function (): void {
    // A booking, an order — someone else's business.
    $invoice = paidInvoice();

    $payment = new class
    {
        public string $payable_type = 'App\\Models\\Booking';

        public int $payable_id = 1;

        public int $amount_cents = 50000;

        public string $reference = 'PMT-BOOKING';

        public string $gateway = 'stripe';
    };

    fireGatewayEvent($payment);

    expect($invoice->fresh()->amount_paid_cents)->toBe(0);
});

it('ignores an event carrying no payment at all', function (): void {
    app(RecordGatewayPayment::class)->handle(new class
    {
        public ?object $payment = null;
    });
})->throwsNoExceptions();
