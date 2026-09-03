<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Invoice;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateInvoiceAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Invoice $invoice, array $data, array $items = []): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items): Invoice {
            if (! empty($items)) {
                $subtotal = 0;
                $invoice->items()->delete();

                foreach ($items as $index => $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $unit = (int) ($item['unit_price_cents'] ?? 0);
                    $total = $qty * $unit;
                    $subtotal += $total;

                    $invoice->items()->create([
                        'description' => $item['description'] ?? 'Line Item',
                        'quantity' => $qty,
                        'unit_price_cents' => $unit,
                        'total_price_cents' => $total,
                        'sort_order' => $index,
                    ]);
                }

                $tax = (int) ($data['tax_cents'] ?? $invoice->tax_cents);
                $discount = (int) ($data['discount_amount_cents'] ?? $invoice->discount_amount_cents);
                $grandTotal = max(0, $subtotal + $tax - $discount);

                $data['subtotal_cents'] = $subtotal;
                $data['grand_total_cents'] = $grandTotal;
            }

            $invoice->update($data);

            return $invoice->fresh(['items']);
        });
    }
}
