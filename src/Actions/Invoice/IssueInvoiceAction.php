<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Invoice;

use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Events\InvoiceIssued;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Lorisleiva\Actions\Concerns\AsAction;

class IssueInvoiceAction
{
    use AsAction;

    public function handle(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Issued,
            'sent_at' => now(),
        ]);

        event(new InvoiceIssued($invoice));

        return $invoice->fresh();
    }
}
