<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Settings;

use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateInvoicingSettingsAction
{
    use AsAction;

    public function __construct(
        protected GetInvoicingSettingsAction $getSettings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): InvoicingSetting
    {
        $setting = $this->getSettings->handle();
        $setting->update($data);

        return $setting->fresh();
    }
}
