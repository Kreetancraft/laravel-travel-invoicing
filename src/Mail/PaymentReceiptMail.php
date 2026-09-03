<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;

/**
 * One acknowledgement per payment.
 *
 * An invoice can be settled in parts — a deposit now, the balance later — and
 * each of those is its own event worth confirming. Sending the invoice again
 * instead would be sending the bill twice, which is how a customer ends up
 * paying twice.
 *
 * So the invoice goes out once and each payment gets a receipt, which also says
 * what is left, so nobody has to work it out.
 */
class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public InvoicePayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        $settled = $this->invoice->isFullyPaid();

        return new Envelope(
            subject: $settled
                ? __('Payment received — invoice :number is settled', ['number' => $this->invoice->invoice_number])
                : __('Payment received for invoice :number', ['number' => $this->invoice->invoice_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'travel-invoicing::emails.payment-receipt',
            with: [
                'invoice' => $this->invoice,
                'payment' => $this->payment,
            ],
        );
    }
}
