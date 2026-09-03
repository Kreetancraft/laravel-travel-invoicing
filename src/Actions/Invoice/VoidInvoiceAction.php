<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Invoice;

use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Lorisleiva\Actions\Concerns\AsAction;

class VoidInvoiceAction
{
    use AsAction;

    public function handle(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => InvoiceStatus::Void,
        ]);

        return $invoice->fresh();
    }
}
