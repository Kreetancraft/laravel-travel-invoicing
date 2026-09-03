<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Listeners;

use Illuminate\Support\Facades\Mail;
use Kreetancraft\TravelInvoicing\Events\InvoiceIssued;
use Kreetancraft\TravelInvoicing\Events\InvoicePaymentRecorded;
use Kreetancraft\TravelInvoicing\Jobs\GenerateInvoicePdfJob;
use Kreetancraft\TravelInvoicing\Mail\InvoiceIssuedMail;
use Kreetancraft\TravelInvoicing\Mail\PaymentReceiptMail;

/**
 * What leaves the building when an invoice is issued or paid.
 *
 * The split matters. The invoice is the demand for payment and goes out once,
 * when it is issued. Each payment gets its own receipt. Re-sending the invoice
 * after a deposit would put two documents in the customer's inbox both claiming
 * to be the bill, which is how somebody pays twice.
 *
 * Both mailables queue themselves, and PDF rendering is a queued job of its own,
 * so nothing here holds up the request that triggered it.
 */
class SendInvoiceDocuments
{
    /**
     * An invoice was issued: render its PDF, then send it.
     */
    public function onIssued(InvoiceIssued $event): void
    {
        // Rendering first, so the email has a file to attach when it goes out.
        // The mailable copes either way — a host with no renderer configured
        // sends the portal link instead.
        if (config('travel-invoicing.pdf.renderer') !== null) {
            GenerateInvoicePdfJob::dispatch($event->invoice->getKey())->afterCommit();
        }

        if (! $this->shouldEmail() || blank($event->invoice->buyer_email)) {
            return;
        }

        Mail::to($event->invoice->buyer_email)->send(new InvoiceIssuedMail($event->invoice));
    }

    /**
     * Money arrived: acknowledge this payment, and only this payment.
     */
    public function onPaymentRecorded(InvoicePaymentRecorded $event): void
    {
        if (! $this->shouldEmail() || blank($event->invoice->buyer_email)) {
            return;
        }

        Mail::to($event->invoice->buyer_email)->send(
            new PaymentReceiptMail($event->invoice->fresh(), $event->payment)
        );
    }

    /**
     * Hosts that send their own mail turn this off rather than working around
     * it. A package quietly emailing a customer is worse than one that does not.
     */
    protected function shouldEmail(): bool
    {
        return (bool) config('travel-invoicing.mail.enabled', false);
    }
}
