<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Kreetancraft\TravelInvoicing\Models\QuoteItem;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 2);
        $unitPrice = $this->faker->numberBetween(50, 500) * 100;

        return [
            'quote_id' => Quote::factory(),
            'title' => $this->faker->randomElement(['Trek Guide & Porter Service', 'Helicopter Return Transfer', 'National Park Entry Permits', 'Kathmandu Hotel Stay']),
            'description' => $this->faker->sentence(),
            'quantity' => $qty,
            'unit_price_cents' => $unitPrice,
            'total_price_cents' => $qty * $unitPrice,
            'sort_order' => 1,
        ];
    }
}
