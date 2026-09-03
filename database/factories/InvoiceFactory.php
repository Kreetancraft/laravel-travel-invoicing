<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 5000) * 100;
        $deposit = (int) round($subtotal * 0.2);

        return [
            'invoice_number' => 'INV-'.date('Y').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'public_token' => Str::random(64),
            'buyer_name' => $this->faker->name(),
            'buyer_email' => $this->faker->safeEmail(),
            'buyer_phone' => $this->faker->phoneNumber(),
            'buyer_country' => $this->faker->country(),
            'title' => $this->faker->randomElement(['Everest Base Camp Trek Invoice', 'Annapurna Circuit Invoice', 'Langtang Valley Invoice']),
            'currency' => 'USD',
            'subtotal_cents' => $subtotal,
            'tax_cents' => 0,
            'discount_amount_cents' => 0,
            'grand_total_cents' => $subtotal,
            'deposit_amount_cents' => $deposit,
            'amount_paid_cents' => 0,
            'status' => InvoiceStatus::Issued,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
        ];
    }
}
