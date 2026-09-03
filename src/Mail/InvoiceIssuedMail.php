<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\TravelInvoicing\Jobs\GenerateInvoicePdfJob;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * The bill, sent once.
 *
 * Once is the important part. An invoice is the demand for payment, and a
 * customer who receives it again after paying a deposit reasonably wonders which
 * of the two they owe. Payments get their own acknowledgement — see
 * PaymentReceiptMail — and the invoice itself is not resent.
 */
class InvoiceIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Invoice :number', ['number' => $this->invoice->invoice_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'travel-invoicing::emails.invoice-issued',
            with: ['invoice' => $this->invoice],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        // Only if the PDF has actually been rendered. It is produced on the
        // queue, so a host with no renderer configured — or an email that beats
        // the job — simply sends the link instead of the file.
        if (blank($this->invoice->pdf_path)) {
            return [];
        }

        $disk = GenerateInvoicePdfJob::disk();

        if (! Storage::disk($disk)->exists($this->invoice->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($disk, $this->invoice->pdf_path)
                ->as($this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
