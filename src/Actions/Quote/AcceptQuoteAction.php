<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Events\QuoteAccepted;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Lorisleiva\Actions\Concerns\AsAction;

class AcceptQuoteAction
{
    use AsAction;

    public function __construct(
        protected ConvertQuoteToInvoiceAction $convertAction,
    ) {}

    public function handle(Quote $quote, ?string $clientNotes = null, bool $autoGenerateInvoice = true): ?Invoice
    {
        return DB::transaction(function () use ($quote, $clientNotes, $autoGenerateInvoice): ?Invoice {
            $quote->update([
                'status' => QuoteStatus::Accepted,
                'responded_at' => now(),
                'client_notes' => $clientNotes ?: $quote->client_notes,
            ]);

            $invoice = null;
            if ($autoGenerateInvoice) {
                $invoice = $this->convertAction->handle($quote);
            }

            event(new QuoteAccepted($quote, $invoice));

            return $invoice;
        });
    }
}
