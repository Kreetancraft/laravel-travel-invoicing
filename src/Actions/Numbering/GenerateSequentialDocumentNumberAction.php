<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Numbering;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Contracts\InvoicingSettingsContract;
use Kreetancraft\TravelInvoicing\Models\DocumentCounter;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateSequentialDocumentNumberAction
{
    use AsAction;

    /**
     * Atomically generate the next sequential document number with row locking.
     *
     * @param  string  $type  'quote' or 'invoice'
     * @return string e.g. "QT-2026-0001" or "INV-2026-0001"
     */
    public function handle(string $type): string
    {
        $year = (int) date('Y');
        $settingsPrefix = null;
        try {
            $settings = app(InvoicingSettingsContract::class)->get();
            $settingsPrefix = $type === 'quote' ? ($settings->quote_prefix ?? null) : ($settings->invoice_prefix ?? null);
        } catch (\Throwable) {
        }
        $prefix = (string) ($settingsPrefix ?? config("travel-invoicing.defaults.{$type}_prefix", config("travel-invoicing.numbering.{$type}_prefix", strtoupper(substr($type, 0, 3)))));
        $padLength = (int) config('travel-invoicing.defaults.pad_length', config('travel-invoicing.numbering.pad_length', 4));
        $includeYear = (bool) config('travel-invoicing.numbering.include_year', true);

        return DB::transaction(function () use ($type, $year, $prefix, $padLength, $includeYear): string {
            $counterClass = config('travel-invoicing.models.document_counter', DocumentCounter::class);

            /** @var DocumentCounter|null $counter */
            $counter = $counterClass::query()
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $counter = new $counterClass([
                    'type' => $type,
                    'year' => $year,
                    'last_value' => 0,
                ]);
            }

            $counter->last_value += 1;
            $counter->save();

            $sequence = str_pad((string) $counter->last_value, $padLength, '0', STR_PAD_LEFT);

            return $includeYear
                ? "{$prefix}-{$year}-{$sequence}"
                : "{$prefix}-{$sequence}";
        });
    }
}
