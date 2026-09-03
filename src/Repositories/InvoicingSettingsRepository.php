<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Repositories;

use Kreetancraft\TravelInvoicing\Actions\Settings\GetInvoicingSettingsAction;
use Kreetancraft\TravelInvoicing\Actions\Settings\UpdateInvoicingSettingsAction;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;

class InvoicingSettingsRepository implements InvoicingSettingsContract
{
    public function __construct(
        protected GetInvoicingSettingsAction $getSettings,
        protected UpdateInvoicingSettingsAction $updateSettings,
    ) {}

    public function get(): InvoicingSetting
    {
        return $this->getSettings->handle();
    }

    public function update(array $data): InvoicingSetting
    {
        return $this->updateSettings->handle($data);
    }
}
