<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Facades;

use Illuminate\Support\Facades\Facade;
use Kreetancraft\TravelInvoicing\TravelInvoicingManager;

/**
 * @method static \Kreetancraft\TravelInvoicing\Models\Quote createQuote(array $data, array $items = [])
 * @method static \Kreetancraft\TravelInvoicing\Models\Invoice createInvoice(array $data, array $items = [])
 * @method static \Kreetancraft\TravelInvoicing\Models\Invoice convertQuote(\Kreetancraft\TravelInvoicing\Models\Quote $quote)
 * @method static \Kreetancraft\TravelInvoicing\Models\InvoicePayment recordPayment(\Kreetancraft\TravelInvoicing\Models\Invoice $invoice, int $amountCents, ?string $gateway = null, ?string $reference = null, ?string $notes = null)
 * @method static \Kreetancraft\TravelInvoicing\Models\InvoicingSetting getSettings()
 * @method static \Kreetancraft\TravelInvoicing\Models\InvoicingSetting updateSettings(array $data)
 * @method static \Kreetancraft\TravelInvoicing\Contracts\QuotesContract quotes()
 * @method static \Kreetancraft\TravelInvoicing\Contracts\InvoicesContract invoices()
 *
 * @see TravelInvoicingManager
 */
class TravelInvoicing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TravelInvoicingManager::class;
    }
}
