<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Console;

use Illuminate\Console\Command;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Models\Invoice;

/**
 * Move invoices past their due date into `overdue`.
 *
 * The status existed and nothing ever wrote it. `isOverdue()` worked it out on
 * the fly from `due_date`, so the invoice list could show a row as overdue while
 * the column said `issued` — and any host filtering or reporting on the column,
 * as the status dropdown does, simply never saw one.
 *
 * A stage nothing can reach is not a stage.
 *
 * Only unpaid invoices move, and `paid` and `void` are left alone: an invoice
 * that was settled late is not overdue afterwards, and a cancelled one is not
 * owed at all.
 */
class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoicing:mark-overdue
        {--dry-run : List what would change without writing anything}';

    protected $description = 'Move invoices past their due date into the overdue stage';

    public function handle(): int
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        $due = $invoiceClass::query()
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereColumn('amount_paid_cents', '<', 'grand_total_cents')
            ->get();

        if ($due->isEmpty()) {
            $this->components->info('No invoices are past their due date.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($due as $invoice) {
                $this->components->twoColumnDetail(
                    $invoice->invoice_number,
                    "due {$invoice->due_date->toDateString()}, {$invoice->formatted_balance_due} outstanding",
                );
            }

            $this->components->info("{$due->count()} invoice(s) would be marked overdue.");

            return self::SUCCESS;
        }

        foreach ($due as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Overdue]);
        }

        $this->components->info("Marked {$due->count()} invoice(s) overdue.");

        return self::SUCCESS;
    }
}
