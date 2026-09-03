<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptQuoteRequest extends FormRequest
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
            'client_notes' => ['nullable', 'string', 'max:1000'],
            'agreed_terms' => ['required', 'accepted'],
        ];
    }
}
