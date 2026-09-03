<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreetancraft\TravelInvoicing\Concerns\HasInvoiceBranding;

/**
 * Invoicing Settings Model.
 *
 * Stores dynamic in-browser company configuration, numbering prefixes,
 * bank transfer payment details, and branding links for invoices & proposals.
 */
class InvoicingSetting extends Model
{
    use HasFactory;
    use HasInvoiceBranding;

    protected $fillable = [
        'business_name',
        'tax_id',
        'address',
        'phone',
        'email',
        'website',
        'currency',
        'quote_prefix',
        'invoice_prefix',
        'pad_length',
        'default_deposit_percent',
        'quote_validity_days',
        'bank_account_details',
        'payment_terms_notes',
        'logo_url',
        'stamp_url',
        'extra_settings',
    ];

    public function getTable(): string
    {
        return (string) config('travel-invoicing.tables.invoicing_settings', 'invoicing_settings');
    }

    protected function casts(): array
    {
        return [
            'pad_length' => 'integer',
            'default_deposit_percent' => 'integer',
            'quote_validity_days' => 'integer',
            'extra_settings' => 'array',
        ];
    }
}
