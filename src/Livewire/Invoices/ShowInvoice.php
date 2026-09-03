<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Invoices;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

class ShowInvoice extends Component
{
    public int $invoiceId;

    // Record Payment form properties
    public int $paymentAmountCents = 0;

    public string $paymentGateway = 'bank_transfer';

    public ?string $paymentReference = null;

    public ?string $paymentNotes = null;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoiceId = (int) $invoice->id;

        $this->paymentAmountCents = $invoice->balanceDueCents();
    }

    public function issueInvoice(InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->issue($invoice);

        Flux::toast(variant: 'success', text: __('Invoice issued.'));
    }

    public function voidInvoice(InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->void($invoice);

        Flux::toast(variant: 'warning', text: __('Invoice marked as void.'));
    }

    public function openRecordPaymentModal(): void
    {
        Flux::modal('record-payment-modal')->show();
    }

    public function recordPayment(InvoicesContract $invoices): void
    {
        $this->validate([
            'paymentAmountCents' => ['required', 'integer', 'min:1'],
            'paymentGateway' => ['required', 'string', 'max:50'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string'],
        ]);

        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->recordPayment(
            $invoice,
            $this->paymentAmountCents,
            $this->paymentGateway,
            $this->paymentReference,
            $this->paymentNotes
        );

        $this->reset(['paymentReference', 'paymentNotes']);
        Flux::modal('record-payment-modal')->close();
        Flux::toast(variant: 'success', text: __('Payment recorded successfully.'));
    }

    #[Title('Invoice Details')]
    public function render(InvoicesContract $invoices): View
    {
        $invoice = $invoices->findOrFail($this->invoiceId);

        return view('travel-invoicing::livewire.invoices.show-invoice', [
            'invoice' => $invoice,
        ])->layout(Layout::admin());
    }
}
