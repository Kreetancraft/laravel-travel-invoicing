<?php

declare(strict_types=1);

use Kreetancraft\TravelInvoicing\Actions\Settings\GetInvoicingSettingsAction;
use Kreetancraft\TravelInvoicing\Actions\Settings\UpdateInvoicingSettingsAction;
use Kreetancraft\TravelInvoicing\Models\InvoicingSetting;

it('retrieves default invoicing settings and allows in-browser configuration update', function () {
    $getSettings = app(GetInvoicingSettingsAction::class);
    $updateSettings = app(UpdateInvoicingSettingsAction::class);

    $settings = $getSettings->handle();

    expect($settings)->toBeInstanceOf(InvoicingSetting::class)
        ->and($settings->business_name)->toBe('Himalayan Trek & Tours')
        ->and($settings->quote_prefix)->toBe('QT')
        ->and($settings->invoice_prefix)->toBe('INV')
        ->and($settings->default_deposit_percent)->toBe(20);

    $updated = $updateSettings->handle([
        'business_name' => 'Apex Himalayan Expeditions',
        'tax_id' => 'PAN: 998877665',
        'quote_prefix' => 'APEX-QT',
        'invoice_prefix' => 'APEX-INV',
        'default_deposit_percent' => 30,
        'bank_account_details' => 'Bank of Everest, Acc: 998811',
    ]);

    expect($updated->business_name)->toBe('Apex Himalayan Expeditions')
        ->and($updated->quote_prefix)->toBe('APEX-QT')
        ->and($updated->invoice_prefix)->toBe('APEX-INV')
        ->and($updated->default_deposit_percent)->toBe(30);
});
