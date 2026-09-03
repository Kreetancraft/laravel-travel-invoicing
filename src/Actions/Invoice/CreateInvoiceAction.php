<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Actions\Invoice;

use Illuminate\Support\Facades\DB;
use Kreetancraft\TravelInvoicing\Actions\Numbering\GenerateSequentialDocumentNumberAction;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Events\InvoiceIssued;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateInvoiceAction
{
    use AsAction;

    public function __construct(
        protected GenerateSequentialDocumentNumberAction $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $data, array $items = []): Invoice
    {
        return DB::transaction(function () use ($data, $items): Invoice {
            $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->numberGenerator->handle('invoice');
            }

            if (empty($data['status'])) {
                $data['status'] = InvoiceStatus::Draft;
            }

            if (empty($data['currency'])) {
                $data['currency'] = config('travel-invoicing.currency', 'USD');
            }

            if (empty($data['issue_date'])) {
                $data['issue_date'] = now()->format('Y-m-d');
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unit = (int) ($item['unit_price_cents'] ?? 0);
                $subtotal += ($qty * $unit);
            }

            if ($subtotal === 0 && isset($data['subtotal_cents'])) {
                $subtotal = (int) $data['subtotal_cents'];
            }

            $discount = (int) ($data['discount_amount_cents'] ?? 0);
            $tax = (int) ($data['tax_cents'] ?? 0);
            $grandTotal = max(0, $subtotal + $tax - $discount);

            $depositAmount = isset($data['deposit_amount_cents'])
                ? (int) $data['deposit_amount_cents']
                : (int) round($grandTotal * (config('travel-invoicing.default_deposit_percent', 20) / 100));

            $data['subtotal_cents'] = $subtotal;
            $data['tax_cents'] = $tax;
            $data['discount_amount_cents'] = $discount;
            $data['grand_total_cents'] = $grandTotal;
            $data['deposit_amount_cents'] = $depositAmount;
            $data['amount_paid_cents'] = (int) ($data['amount_paid_cents'] ?? 0);

            /** @var Invoice $invoice */
            $invoice = $invoiceClass::create($data);

            foreach ($items as $index => $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unit = (int) ($item['unit_price_cents'] ?? 0);
                $invoice->items()->create([
                    'description' => $item['description'] ?? 'Line Item',
                    'quantity' => $qty,
                    'unit_price_cents' => $unit,
                    'total_price_cents' => $qty * $unit,
                    'sort_order' => $index,
                ]);
            }

            if ($invoice->status === InvoiceStatus::Issued) {
                event(new InvoiceIssued($invoice));
            }

            return $invoice->load('items');
        });
    }
}
