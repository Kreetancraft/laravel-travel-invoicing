<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kreetancraft\TravelInvoicing\Models\Quote;

class QuoteRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Quote $quote,
        public ?string $reason = null,
    ) {}
}
