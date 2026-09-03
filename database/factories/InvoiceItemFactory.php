<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoiceItem;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = 1;
        $unitPrice = $this->faker->numberBetween(500, 2500) * 100;

        return [
            'invoice_id' => Invoice::factory(),
            'description' => $this->faker->sentence(4),
            'quantity' => $qty,
            'unit_price_cents' => $unitPrice,
            'total_price_cents' => $qty * $unitPrice,
            'sort_order' => 1,
        ];
    }
}
