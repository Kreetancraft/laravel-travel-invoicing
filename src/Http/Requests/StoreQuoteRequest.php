<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'discount_amount_cents' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'discount_note' => ['nullable', 'string', 'max:255'],
            'deposit_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
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
}
