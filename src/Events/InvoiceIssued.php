<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kreetancraft\TravelInvoicing\Models\Invoice;

class InvoiceIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {}
}
