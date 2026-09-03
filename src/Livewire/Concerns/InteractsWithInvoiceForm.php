<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Concerns;

use Illuminate\Validation\Rule;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;

trait InteractsWithInvoiceForm
{
    public ?int $quote_id = null;

    public ?int $customer_id = null;

    public string $buyer_name = '';

    public string $buyer_email = '';

    public ?string $buyer_phone = null;

    public ?string $buyer_country = null;

    public ?string $buyer_address = null;

    public ?string $title = null;

    public string $currency = 'USD';

    public int $tax_cents = 0;

    public int $discount_amount_cents = 0;

    public ?string $coupon_code = null;

    public int $deposit_amount_cents = 0;

    public string $status = 'draft';

    public string $issue_date = '';

    public ?string $due_date = null;

    public ?string $notes = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $items = [];

    /**
     * @return array<string, list<mixed>>
     */
    protected function invoiceRules(): array
    {
        return [
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'buyer_country' => ['nullable', 'string', 'max:100'],
            'buyer_address' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'discount_amount_cents' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'deposit_amount_cents' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ];
    }

    public function addItemRow(string $description = '', int $quantity = 1, int $unitPriceCents = 0): void
    {
        $this->items[] = [
            'description' => $description,
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
        return max(0, $this->subtotalCents + $this->tax_cents - $this->discount_amount_cents);
    }

    protected function fillFromInvoice(Invoice $invoice): void
    {
        $this->quote_id = $invoice->quote_id ? (int) $invoice->quote_id : null;
        $this->customer_id = $invoice->customer_id ? (int) $invoice->customer_id : null;
        $this->buyer_name = $invoice->buyer_name;
        $this->buyer_email = $invoice->buyer_email;
        $this->buyer_phone = $invoice->buyer_phone;
        $this->buyer_country = $invoice->buyer_country;
        $this->buyer_address = $invoice->buyer_address;
        $this->title = $invoice->title;
        $this->currency = $invoice->currency;
        $this->tax_cents = $invoice->tax_cents;
        $this->discount_amount_cents = $invoice->discount_amount_cents;
        $this->coupon_code = $invoice->coupon_code;
        $this->deposit_amount_cents = $invoice->deposit_amount_cents;
        $this->status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        $this->issue_date = $invoice->issue_date->format('Y-m-d');
        $this->due_date = $invoice->due_date?->format('Y-m-d');
        $this->notes = $invoice->notes;

        $this->items = $invoice->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price_cents' => $item->unit_price_cents,
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getInvoiceFormData(): array
    {
        return [
            'quote_id' => $this->quote_id ?: null,
            'customer_id' => $this->customer_id ?: null,
            'buyer_name' => $this->buyer_name,
            'buyer_email' => strtolower(trim($this->buyer_email)),
            'buyer_phone' => $this->buyer_phone ?: null,
            'buyer_country' => $this->buyer_country ?: null,
            'buyer_address' => $this->buyer_address ?: null,
            'title' => $this->title ?: null,
            'currency' => $this->currency,
            'tax_cents' => $this->tax_cents,
            'discount_amount_cents' => $this->discount_amount_cents,
            'coupon_code' => $this->coupon_code ?: null,
            'deposit_amount_cents' => $this->deposit_amount_cents,
            'status' => $this->status ?: 'draft',
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date ?: null,
            'notes' => $this->notes ?: null,
        ];
    }
}
