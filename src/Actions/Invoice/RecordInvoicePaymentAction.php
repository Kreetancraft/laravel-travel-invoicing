<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Invoice;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Events\InvoicePaid;
use Kreetancraft\TravelInvoicing\Events\InvoicePaymentRecorded;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordInvoicePaymentAction
{
    use AsAction;

    public function handle(
        Invoice $invoice,
        int $amountCents,
        ?string $gateway = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): InvoicePayment {
        return DB::transaction(function () use ($invoice, $amountCents, $gateway, $reference, $notes): InvoicePayment {
            $paymentClass = config('travel-invoicing.models.invoice_payment', InvoicePayment::class);
            $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

            // Lock the invoice for the whole recording. Two payments landing at
            // once both used to read `amount_paid_cents` off their own copy of
            // the model, add to it, and write back — so the second silently
            // overwrote the first and the invoice under-counted.
            /** @var Invoice $invoice */
            $invoice = $invoiceClass::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            // The same gateway payment must never be recorded twice. A webhook
            // is delivered more than once as a matter of routine — that is how
            // gateways guarantee delivery at all — and without this the invoice
            // is credited again for money that arrived once.
            if (filled($reference)) {
                $already = $paymentClass::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('transaction_reference', $reference)
                    ->first();

                if ($already !== null) {
                    return $already;
                }
            }

            /** @var InvoicePayment $payment */
            $payment = $paymentClass::create([
                'invoice_id' => $invoice->id,
                'transaction_reference' => $reference,
                'gateway' => $gateway,
                'amount_cents' => $amountCents,
                'currency' => $invoice->currency,
                'status' => 'succeeded',
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            // Summed from the payments themselves, not added to a running total
            // on the invoice. The rows are the record of what was paid; the
            // column is a convenience copy of them, and recomputing it means the
            // two can never drift apart.
            $newPaidTotal = (int) $paymentClass::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'succeeded')
                ->sum('amount_cents');

            $newStatus = $invoice->status;
            $paidAt = $invoice->paid_at;

            if ($newPaidTotal >= $invoice->grand_total_cents && $invoice->grand_total_cents > 0) {
                $newStatus = InvoiceStatus::Paid;
                $paidAt = now();
            } elseif ($newPaidTotal > 0) {
                $newStatus = InvoiceStatus::PartiallyPaid;
            }

            $invoice->update([
                'amount_paid_cents' => $newPaidTotal,
                'status' => $newStatus,
                'paid_at' => $paidAt,
            ]);

            event(new InvoicePaymentRecorded($invoice, $payment));

            if ($newStatus === InvoiceStatus::Paid) {
                event(new InvoicePaid($invoice));
            }

            return $payment;
        });
    }
}
