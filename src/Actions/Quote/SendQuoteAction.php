<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Lorisleiva\Actions\Concerns\AsAction;

class SendQuoteAction
{
    use AsAction;

    public function handle(Quote $quote): Quote
    {
        $quote->update([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]);

        return $quote->fresh();
    }
}
