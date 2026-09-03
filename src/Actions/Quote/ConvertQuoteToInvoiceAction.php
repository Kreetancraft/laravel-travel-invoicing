<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Actions\Numbering\GenerateSequentialDocumentNumberAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Events\InvoiceIssued;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Lorisleiva\Actions\Concerns\AsAction;

class ConvertQuoteToInvoiceAction
{
    use AsAction;

    public function __construct(
        protected GenerateSequentialDocumentNumberAction $numberGenerator,
    ) {}

    public function handle(Quote $quote): Invoice
    {
        return DB::transaction(function () use ($quote): Invoice {
            $quote->loadMissing('items');

            $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

            // One invoice per quote. Nothing stopped this running twice — an
            // impatient click, a retried job, an accept that ran again — and each
            // run minted a fresh invoice number for the same money owed.
            $existing = $invoiceClass::query()->where('quote_id', $quote->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            /** @var Invoice $invoice */
            $invoice = $invoiceClass::create([
                'invoice_number' => $this->numberGenerator->handle('invoice'),
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'buyer_name' => $quote->buyer_name,
                'buyer_email' => $quote->buyer_email,
                'buyer_phone' => $quote->buyer_phone,
                'buyer_country' => $quote->buyer_country,
                'buyer_address' => $quote->buyer_address,
                'title' => $quote->title,
                'currency' => $quote->currency,
                'subtotal_cents' => $quote->subtotal_cents,
                'tax_cents' => $quote->tax_cents,
                'discount_amount_cents' => $quote->discount_amount_cents,
                'coupon_code' => $quote->coupon_code,
                'grand_total_cents' => $quote->grand_total_cents,
                'deposit_amount_cents' => $quote->deposit_amount_cents,
                'amount_paid_cents' => 0,
                'status' => InvoiceStatus::Issued,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(14)->format('Y-m-d'),
                'sent_at' => now(),
                'notes' => $quote->client_notes,
            ]);

            foreach ($quote->items as $item) {
                $invoice->items()->create([
                    'description' => $item->title.($item->description ? ' — '.$item->description : ''),
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_price_cents' => $item->total_price_cents,
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Converting is an acceptance in substance: an invoice now exists for
            // this quote, and leaving it in draft meant the quote list showed
            // something as unanswered that had already been billed.
            if ($quote->status !== QuoteStatus::Accepted) {
                $quote->update([
                    'status' => QuoteStatus::Accepted,
                    'responded_at' => $quote->responded_at ?? now(),
                ]);
            }

            event(new InvoiceIssued($invoice));

            return $invoice->load('items');
        });
    }
}
