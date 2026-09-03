<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Events\QuoteRejected;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Lorisleiva\Actions\Concerns\AsAction;

class RejectQuoteAction
{
    use AsAction;

    public function handle(Quote $quote, ?string $reason = null): Quote
    {
        $quote->update([
            'status' => QuoteStatus::Rejected,
            'responded_at' => now(),
            'rejection_reason' => $reason,
        ]);

        event(new QuoteRejected($quote, $reason));

        return $quote->fresh();
    }
}
