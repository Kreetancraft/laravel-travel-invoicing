<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Quotes;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Livewire\Attributes\Title;
use Livewire\Component;

class ShowQuote extends Component
{
    public int $quoteId;

    public function mount(Quote $quote): void
    {
        $this->authorize('view', $quote);
        $this->quoteId = (int) $quote->id;
    }

    public function sendQuote(QuotesContract $quotes): void
    {
        $quote = $quotes->findOrFail($this->quoteId);
        $this->authorize('update', $quote);

        $quotes->send($quote);

        Flux::toast(variant: 'success', text: __('Quote sent to client.'));
    }

    public function convertToInvoice(QuotesContract $quotes): void
    {
        $quote = $quotes->findOrFail($this->quoteId);
        $this->authorize('update', $quote);

        $invoice = $quotes->accept($quote, null, true);

        Flux::toast(variant: 'success', text: __('Converted to Invoice #').$invoice->invoice_number);

        $this->redirectRoute('admin.invoices.show', ['invoice' => $invoice->id], navigate: true);
    }

    #[Title('Quote Proposal Details')]
    public function render(QuotesContract $quotes): View
    {
        $quote = $quotes->findOrFail($this->quoteId);

        return view('travel-invoicing::livewire.quotes.show-quote', [
            'quote' => $quote,
        ])->layout(Layout::admin());
    }
}
