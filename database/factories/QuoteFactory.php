<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Models\Quote;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 5000) * 100;
        $depositPercent = 20;

        return [
            'quote_reference' => 'QT-'.date('Y').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'public_token' => Str::random(64),
            'buyer_name' => $this->faker->name(),
            'buyer_email' => $this->faker->safeEmail(),
            'buyer_phone' => $this->faker->phoneNumber(),
            'buyer_country' => $this->faker->country(),
            'title' => $this->faker->randomElement(['Everest Base Camp Trek Proposal', 'Annapurna Circuit Package', 'Manaslu Expedition Estimate']),
            'currency' => 'USD',
            'subtotal_cents' => $subtotal,
            'discount_amount_cents' => 0,
            'grand_total_cents' => $subtotal,
            'deposit_percent' => $depositPercent,
            'deposit_amount_cents' => (int) round($subtotal * $depositPercent / 100),
            'status' => QuoteStatus::Draft,
            'valid_until' => now()->addDays(14),
        ];
    }
}
