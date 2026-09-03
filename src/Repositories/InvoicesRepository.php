<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Kreetancraft\TravelInvoicing\Actions\Invoice\CreateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\IssueInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\RecordInvoicePaymentAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\UpdateInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Invoice\VoidInvoiceAction;
use Kreetancraft\TravelInvoicing\Actions\Quote\ConvertQuoteToInvoiceAction;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Kreetancraft\TravelInvoicing\Models\InvoicePayment;
use Kreetancraft\TravelInvoicing\Models\Quote;

class InvoicesRepository implements InvoicesContract
{
    public function __construct(
        protected CreateInvoiceAction $createInvoice,
        protected UpdateInvoiceAction $updateInvoice,
        protected IssueInvoiceAction $issueInvoice,
        protected RecordInvoicePaymentAction $recordPaymentAction,
        protected VoidInvoiceAction $voidInvoice,
        protected ConvertQuoteToInvoiceAction $convertFromQuoteAction,
    ) {}

    public function create(array $data, array $items = []): Invoice
    {
        return $this->createInvoice->handle($data, $items);
    }

    public function update(Invoice $invoice, array $data, array $items = []): Invoice
    {
        return $this->updateInvoice->handle($invoice, $data, $items);
    }

    public function createFromQuote(Quote $quote): Invoice
    {
        return $this->convertFromQuoteAction->handle($quote);
    }

    public function findOrFail(int $id): Invoice
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        return $invoiceClass::with(['items', 'payments', 'quote'])->findOrFail($id);
    }

    public function findByToken(string $token): ?Invoice
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);

        return $invoiceClass::with(['items', 'payments'])->where('public_token', $token)->first();
    }

    public function paginate(?string $search, ?string $status, ?string $sort = '-created_at', int $perPage = 15): LengthAwarePaginator
    {
        $invoiceClass = config('travel-invoicing.models.invoice', Invoice::class);
        $allowed = ['created_at', 'due_date', 'grand_total_cents', 'status', 'invoice_number'];
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (! in_array($field, $allowed, true)) {
            $field = 'created_at';
            $direction = 'desc';
        }
        $escaped = $search !== null ? addcslashes($search, '%_') : null;

        return $invoiceClass::query()
            ->with(['items', 'payments'])
            ->when(filled($escaped), fn (Builder $q) => $q->search((string) $escaped))
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->orderBy($field, $direction)
            ->paginate($perPage);
    }

    public function issue(Invoice $invoice): Invoice
    {
        return $this->issueInvoice->handle($invoice);
    }

    public function recordPayment(
        Invoice $invoice,
        int $amountCents,
        ?string $gateway = 'manual',
        ?string $reference = null,
        ?string $notes = null
    ): InvoicePayment {
        return $this->recordPaymentAction->handle($invoice, $amountCents, $gateway, $reference, $notes);
    }

    public function void(Invoice $invoice): Invoice
    {
        return $this->voidInvoice->handle($invoice);
    }

    public function delete(int $id): void
    {
        $invoice = $this->findOrFail($id);
        $invoice->delete();
    }
}
