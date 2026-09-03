<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Quotes;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Livewire\Concerns\InteractsWithQuoteForm;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Livewire\Attributes\Title;
use Livewire\Component;

class EditQuote extends Component
{
    use InteractsWithQuoteForm;

    public int $quoteId;

    public function mount(Quote $quote): void
    {
        $this->authorize('update', $quote);
        $this->quoteId = (int) $quote->id;

        $quote->loadMissing('items');
        $this->fillFromQuote($quote);
    }

    public function save(QuotesContract $quotes): void
    {
        $this->validate($this->quoteRules());

        $quote = $quotes->findOrFail($this->quoteId);
        $this->authorize('update', $quote);

        $quotes->update($quote, $this->getQuoteFormData(), $this->items);

        Flux::toast(variant: 'success', text: __('Quote updated successfully.'));

        $this->redirectRoute('admin.quotes.show', ['quote' => $quote->id], navigate: true);
    }

    #[Title('Edit Quote / Proposal')]
    public function render(): View
    {
        return view('travel-invoicing::livewire.quotes.edit-quote')
            ->layout(Layout::admin());
    }
}
