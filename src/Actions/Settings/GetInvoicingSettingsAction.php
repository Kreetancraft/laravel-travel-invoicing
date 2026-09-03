<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Settings;

use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Lorisleiva\Actions\Concerns\AsAction;

class GetInvoicingSettingsAction
{
    use AsAction;

    public function handle(): InvoicingSetting
    {
        $settingClass = config('travel-invoicing.models.invoicing_setting', InvoicingSetting::class);

        /** @var InvoicingSetting|null $setting */
        $setting = $settingClass::first();

        if ($setting === null) {
            $defaults = config('travel-invoicing.defaults', []);

            $setting = $settingClass::create([
                'business_name' => $defaults['business_name'] ?? 'Himalayan Trek & Tours',
                'tax_id' => $defaults['tax_id'] ?? 'PAN/VAT: 601234567',
                'address' => $defaults['address'] ?? 'Thamel Marg, Kathmandu 44600, Nepal',
                'phone' => $defaults['phone'] ?? '+977 1 4700000',
                'email' => $defaults['email'] ?? 'billing@himalayantrek.com',
                'website' => $defaults['website'] ?? 'https://himalayantrek.com',
                'currency' => $defaults['currency'] ?? 'USD',
                'quote_prefix' => $defaults['quote_prefix'] ?? 'QT',
                'invoice_prefix' => $defaults['invoice_prefix'] ?? 'INV',
                'pad_length' => (int) ($defaults['pad_length'] ?? 4),
                'default_deposit_percent' => (int) ($defaults['default_deposit_percent'] ?? 20),
                'quote_validity_days' => (int) ($defaults['quote_validity_days'] ?? 14),
                'bank_account_details' => $defaults['bank_account_details'] ?? null,
                'payment_terms_notes' => $defaults['payment_terms_notes'] ?? null,
            ]);
        }

        return $setting;
    }
}
