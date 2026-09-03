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

class CreateQuote extends Component
{
    use InteractsWithQuoteForm;

    public function mount(): void
    {
        $this->authorize('create', Quote::class);

        $this->currency = (string) config('travel-invoicing.currency', 'USD');
        $this->valid_until = now()->addDays((int) config('travel-invoicing.quote_validity_days', 14))->format('Y-m-d');
        $this->deposit_percent = (int) config('travel-invoicing.default_deposit_percent', 20);

        $this->addItemRow('Trek Package / Service', 1, 100000);
    }

    public function save(QuotesContract $quotes): void
    {
        $this->validate($this->quoteRules());

        $quote = $quotes->create($this->getQuoteFormData(), $this->items);

        Flux::toast(variant: 'success', text: __('Quote created successfully.'));

        $this->redirectRoute('admin.quotes.show', ['quote' => $quote->id], navigate: true);
    }

    #[Title('Create Quote / Proposal')]
    public function render(): View
    {
        return view('travel-invoicing::livewire.quotes.create-quote')
            ->layout(Layout::admin());
    }
}
