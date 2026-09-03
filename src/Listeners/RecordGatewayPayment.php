<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Listeners;

use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * Credit an invoice when a payment gateway says money arrived.
 *
 * This is the join between this package and a payment package, and it is written
 * so that neither has to know the other exists. The event is subscribed by its
 * string class name, so on a host without a payment package it simply never
 * fires; and the event object is read by duck typing rather than by importing a
 * class, so nothing here breaks if that package changes shape.
 *
 * It only handles the case where the invoice already existed and was the thing
 * being paid for. The other direction — pay first, invoice afterwards, as an
 * e-commerce order does — needs to know how to turn whatever was bought into
 * line items, which is the host's business and not something this package can
 * guess. The README shows how to write it.
 */
class RecordGatewayPayment
{
    public function __construct(
        protected RecordInvoicePaymentAction $recordPayment,
    ) {}

    public function handle(object $event): void
    {
        $payment = $event->payment ?? null;

        if (! is_object($payment)) {
            return;
        }

        $invoice = $this->invoiceFor($payment);

        if ($invoice === null) {
            return;
        }

        // Recording is idempotent on the reference, so a webhook delivered twice
        // — which is normal, not exceptional — credits the invoice once.
        $this->recordPayment->handle(
            $invoice,
            (int) ($payment->amount_cents ?? 0),
            (string) ($payment->gateway ?? 'gateway'),
            $this->referenceFor($payment),
        );
    }

    /**
     * The invoice this payment was for, if it was for one at all.
     */
    protected function invoiceFor(object $payment): ?Invoice
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        $payableType = $payment->payable_type ?? null;
        $payableId = $payment->payable_id ?? null;

        if (blank($payableType) || blank($payableId)) {
            return null;
        }

        // The payment was for something else this host sells — a booking, an
        // order. Not our business.
        if (! is_a($payableType, $invoiceClass, true)) {
            return null;
        }

        return $invoiceClass::query()->find($payableId);
    }

    /**
     * What to deduplicate on.
     *
     * The payment's own reference is preferred over the gateway's, because it is
     * stable from the moment the payment row is written, while a gateway
     * reference can arrive later or change shape between a session and a charge.
     */
    protected function referenceFor(object $payment): ?string
    {
        foreach (['reference', 'gateway_reference', 'uuid'] as $candidate) {
            if (filled($payment->{$candidate} ?? null)) {
                return (string) $payment->{$candidate};
            }
        }

        return null;
    }
}
