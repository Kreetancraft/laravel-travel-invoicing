<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Invoices;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Livewire\Concerns\InteractsWithInvoiceForm;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

class CreateInvoice extends Component
{
    use InteractsWithInvoiceForm;

    public function mount(): void
    {
        $this->authorize('create', Invoice::class);

        $this->currency = (string) config('travel-invoicing.currency', 'USD');
        $this->issue_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');

        $this->addItemRow('Trekking Service / Package', 1, 100000);
    }

    public function save(InvoicesContract $invoices): void
    {
        $this->validate($this->invoiceRules());

        $invoice = $invoices->create($this->getInvoiceFormData(), $this->items);

        Flux::toast(variant: 'success', text: __('Invoice created successfully.'));

        $this->redirectRoute('admin.invoices.show', ['invoice' => $invoice->id], navigate: true);
    }

    #[Title('Create Tax Invoice')]
    public function render(): View
    {
        return view('travel-invoicing::livewire.invoices.create-invoice')
            ->layout(Layout::admin());
    }
}
