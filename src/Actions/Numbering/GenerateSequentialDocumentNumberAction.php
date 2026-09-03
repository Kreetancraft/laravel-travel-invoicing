<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Numbering;

use Illuminate\Database\QueryException;
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

            $nextValue = $this->claimNextValue($counterClass, $type, $year);

            $sequence = str_pad((string) $nextValue, $padLength, '0', STR_PAD_LEFT);

            return $includeYear
                ? "{$prefix}-{$year}-{$sequence}"
                : "{$prefix}-{$sequence}";
        });
    }

    /**
     * Take the next number in this type's sequence for this year.
     *
     * Written through the query builder rather than `$counter->save()`, because
     * the counter's key is the pair (type, year) and Eloquent cannot express a
     * composite key — `$primaryKey = ['type', 'year']` makes `save()` on an
     * existing row throw "Cannot access offset of type array on array".
     *
     * That meant the first document of a year was created fine and the **second
     * one failed**, every year, for both invoices and quotes. It went unnoticed
     * because each test starts with an empty database and most create one
     * document.
     *
     * The row is locked before it is read so two requests cannot both take the
     * same number. The insert is guarded separately: on the very first document
     * of a year there is no row to lock, so two requests can both find nothing —
     * the loser of that race catches the duplicate and re-reads instead.
     */
    protected function claimNextValue(string $counterClass, string $type, int $year): int
    {
        $counter = $counterClass::query()
            ->where('type', $type)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($counter !== null) {
            $next = (int) $counter->last_value + 1;

            $counterClass::query()
                ->where('type', $type)
                ->where('year', $year)
                ->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        }

        try {
            $counterClass::query()->insert([
                'type' => $type,
                'year' => $year,
                'last_value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 1;
        } catch (QueryException) {
            // Someone else created the row between our read and our insert.
            return $this->claimNextValue($counterClass, $type, $year);
        }
    }
}
