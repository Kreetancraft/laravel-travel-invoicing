<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Quote;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateQuoteAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Quote $quote, array $data, array $items = []): Quote
    {
        return DB::transaction(function () use ($quote, $data, $items): Quote {
            if (! empty($items)) {
                $subtotal = 0;
                $quote->items()->delete();

                foreach ($items as $index => $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $unit = (int) ($item['unit_price_cents'] ?? $item['unit_price'] ?? 0);
                    $total = $qty * $unit;
                    $subtotal += $total;

                    $quote->items()->create([
                        'title' => $item['title'] ?? 'Item',
                        'description' => $item['description'] ?? null,
                        'quantity' => $qty,
                        'unit_price_cents' => $unit,
                        'total_price_cents' => $total,
                        'sort_order' => $index,
                    ]);
                }

                $discount = (int) ($data['discount_amount_cents'] ?? $quote->discount_amount_cents);
                $grandTotal = max(0, $subtotal - $discount);
                $depositPercent = (int) ($data['deposit_percent'] ?? $quote->deposit_percent);
                $depositAmount = (int) round($grandTotal * $depositPercent / 100);

                $data['subtotal_cents'] = $subtotal;
                $data['grand_total_cents'] = $grandTotal;
                $data['deposit_percent'] = $depositPercent;
                $data['deposit_amount_cents'] = $depositAmount;
            }

            $quote->update($data);

            return $quote->fresh(['items']);
        });
    }
}
