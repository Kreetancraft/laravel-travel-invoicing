<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Contracts;

use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;

interface InvoicingSettingsContract
{
    public function get(): InvoicingSetting;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): InvoicingSetting;
}
