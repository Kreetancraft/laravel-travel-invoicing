<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Facades;

use Illuminate\Support\Facades\Facade;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Repositories\InvoicesRepository;
use Kreetancraft\TravelInvoicing\Repositories\InvoicingSettingsRepository;
use Kreetancraft\TravelInvoicing\Repositories\QuotesRepository;

/**
 * @method static \Kreetancraft\TravelInvoicing\Models\Quote createQuote(array $data)
 * @method static \Kreetancraft\TravelInvoicing\Models\Invoice createInvoice(array $data)
 * @method static \Kreetancraft\TravelInvoicing\Models\InvoicingSetting getSettings()
 *
 * @see QuotesRepository
 * @see InvoicesRepository
 * @see InvoicingSettingsRepository
 */
class TravelInvoicing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QuotesContract::class;
    }
}
