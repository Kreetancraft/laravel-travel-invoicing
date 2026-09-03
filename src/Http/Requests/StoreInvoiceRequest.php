<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:50'],
            'buyer_country' => ['nullable', 'string', 'max:100'],
            'buyer_address' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'discount_amount_cents' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'deposit_amount_cents' => ['nullable', 'integer', 'min:0'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
