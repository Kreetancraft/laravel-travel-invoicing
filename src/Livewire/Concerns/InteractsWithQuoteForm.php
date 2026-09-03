<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Concerns;

use Illuminate\Validation\Rule;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Models\Quote;

trait InteractsWithQuoteForm
{
    public ?int $customer_id = null;

    public string $buyer_name = '';

    public string $buyer_email = '';

    public ?string $buyer_phone = null;

    public ?string $buyer_country = null;

    public ?string $buyer_address = null;

    public string $title = '';

    public string $currency = 'USD';

    public int $discount_amount_cents = 0;

    public ?string $coupon_code = null;

    public ?string $discount_note = null;

    public int $deposit_percent = 20;

    public string $status = 'draft';

    public string $valid_until = '';

    public ?string $client_notes = null;

    public ?string $internal_notes = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $items = [];

    /**
     * @return array<string, list<mixed>>
     */
    protected function quoteRules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'buyer_country' => ['nullable', 'string', 'max:100'],
            'buyer_address' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'discount_amount_cents' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'discount_note' => ['nullable', 'string', 'max:255'],
            'deposit_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'valid_until' => ['required', 'date'],
            'client_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ];
    }

    public function addItemRow(string $title = '', int $quantity = 1, int $unitPriceCents = 0): void
    {
        $this->items[] = [
            'title' => $title,
            'description' => '',
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
        ];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getSubtotalCentsProperty(): int
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (int) ($item['unit_price_cents'] ?? 0);
            $sum += ($qty * $price);
        }

        return $sum;
    }

    public function getGrandTotalCentsProperty(): int
    {
        return max(0, $this->subtotalCents - $this->discount_amount_cents);
    }

    public function getDepositAmountCentsProperty(): int
    {
        return (int) round($this->grandTotalCents * $this->deposit_percent / 100);
    }

    protected function fillFromQuote(Quote $quote): void
    {
        $this->customer_id = $quote->customer_id ? (int) $quote->customer_id : null;
        $this->buyer_name = $quote->buyer_name;
        $this->buyer_email = $quote->buyer_email;
        $this->buyer_phone = $quote->buyer_phone;
        $this->buyer_country = $quote->buyer_country;
        $this->buyer_address = $quote->buyer_address;
        $this->title = $quote->title;
        $this->currency = $quote->currency;
        $this->discount_amount_cents = $quote->discount_amount_cents;
        $this->coupon_code = $quote->coupon_code;
        $this->discount_note = $quote->discount_note;
        $this->deposit_percent = $quote->deposit_percent;
        $this->status = $quote->status instanceof QuoteStatus ? $quote->status->value : (string) $quote->status;
        $this->valid_until = $quote->valid_until->format('Y-m-d');
        $this->client_notes = $quote->client_notes;
        $this->internal_notes = $quote->internal_notes;

        $this->items = $quote->items->map(fn ($item) => [
            'title' => $item->title,
            'description' => $item->description ?? '',
            'quantity' => $item->quantity,
            'unit_price_cents' => $item->unit_price_cents,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getQuoteFormData(): array
    {
        return [
            'customer_id' => $this->customer_id ?: null,
            'buyer_name' => $this->buyer_name,
            'buyer_email' => strtolower(trim($this->buyer_email)),
            'buyer_phone' => $this->buyer_phone ?: null,
            'buyer_country' => $this->buyer_country ?: null,
            'buyer_address' => $this->buyer_address ?: null,
            'title' => $this->title,
            'currency' => $this->currency,
            'discount_amount_cents' => $this->discount_amount_cents,
            'coupon_code' => $this->coupon_code ?: null,
            'discount_note' => $this->discount_note ?: null,
            'deposit_percent' => $this->deposit_percent,
            'status' => $this->status ?: 'draft',
            'valid_until' => $this->valid_until,
            'client_notes' => $this->client_notes ?: null,
            'internal_notes' => $this->internal_notes ?: null,
        ];
    }
}
