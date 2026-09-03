<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Actions\Numbering\GenerateSequentialDocumentNumberAction;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Events\QuoteCreated;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Kreetancraft\TravelInvoicing\Support\CustomerLink;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateQuoteAction
{
    use AsAction;

    public function __construct(
        protected GenerateSequentialDocumentNumberAction $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $data, array $items = []): Quote
    {
        return DB::transaction(function () use ($data, $items): Quote {
            $quoteClass = config('travel-invoicing.models.quote', Quote::class);

            // Same seam as the invoice: snapshot the buyer on the document, and
            // link it to a customer record when the host has one.
            $data['customer_id'] ??= CustomerLink::idFor($data['buyer_email'] ?? null, [
                'name' => $data['buyer_name'] ?? null,
                'phone' => $data['buyer_phone'] ?? null,
                'country' => $data['buyer_country'] ?? null,
            ]);

            if (empty($data['quote_reference'])) {
                $data['quote_reference'] = $this->numberGenerator->handle('quote');
            }

            if (empty($data['status'])) {
                $data['status'] = QuoteStatus::Draft;
            }

            if (empty($data['currency'])) {
                $data['currency'] = config('travel-invoicing.currency', 'USD');
            }

            if (empty($data['valid_until'])) {
                $days = (int) config('travel-invoicing.quote_validity_days', 14);
                $data['valid_until'] = now()->addDays($days)->format('Y-m-d');
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unit = (int) ($item['unit_price_cents'] ?? 0);
                $subtotal += ($qty * $unit);
            }

            if ($subtotal === 0 && isset($data['subtotal_cents'])) {
                $subtotal = (int) $data['subtotal_cents'];
            }

            $discount = (int) ($data['discount_amount_cents'] ?? 0);
            $tax = (int) ($data['tax_cents'] ?? 0);
            $grandTotal = max(0, $subtotal + $tax - $discount);

            $depositPercent = (int) ($data['deposit_percent'] ?? config('travel-invoicing.default_deposit_percent', 20));
            $depositAmount = (int) round($grandTotal * $depositPercent / 100);

            $data['subtotal_cents'] = $subtotal;
            $data['tax_cents'] = $tax;
            $data['discount_amount_cents'] = $discount;
            $data['grand_total_cents'] = $grandTotal;
            $data['deposit_percent'] = $depositPercent;
            $data['deposit_amount_cents'] = $depositAmount;

            /** @var Quote $quote */
            $quote = $quoteClass::create($data);

            foreach ($items as $index => $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unit = (int) ($item['unit_price_cents'] ?? 0);
                $quote->items()->create([
                    'title' => $item['title'] ?? 'Line Item',
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price_cents' => $unit,
                    'total_price_cents' => $qty * $unit,
                    'sort_order' => $index,
                ]);
            }

            event(new QuoteCreated($quote));

            return $quote->load('items');
        });
    }
}
