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

class EditInvoice extends Component
{
    use InteractsWithInvoiceForm;

    public int $invoiceId;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);
        $this->invoiceId = (int) $invoice->id;

        $invoice->loadMissing('items');
        $this->fillFromInvoice($invoice);
    }

    public function save(InvoicesContract $invoices): void
    {
        $this->validate($this->invoiceRules());

        $invoice = $invoices->findOrFail($this->invoiceId);
        $this->authorize('update', $invoice);

        $invoices->update($invoice, $this->getInvoiceFormData(), $this->items);

        Flux::toast(variant: 'success', text: __('Invoice updated successfully.'));

        $this->redirectRoute('admin.invoices.show', ['invoice' => $invoice->id], navigate: true);
    }

    #[Title('Edit Tax Invoice')]
    public function render(): View
    {
        return view('travel-invoicing::livewire.invoices.edit-invoice')
            ->layout(Layout::admin());
    }
}
