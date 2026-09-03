<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Quotes;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\QuotesContract;
use Kreetancraft\TravelInvoicing\Enums\QuoteStatus;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\Quote;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManageQuotes extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $sort = '-created_at';

    public ?int $pendingDeleteId = null;

    /**
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sort' => ['except' => '-created_at'],
    ];

    public function sortBy(string $field): void
    {
        $this->sort = $this->sort === $field ? "-{$field}" : $field;
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Quote::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sendQuote(int $id, QuotesContract $quotes): void
    {
        $quote = $quotes->findOrFail($id);
        $this->authorize('update', $quote);

        $quotes->send($quote);

        Flux::toast(variant: 'success', text: __('Quote marked as sent to client.'));
    }

    public function convertToInvoice(int $id, QuotesContract $quotes): void
    {
        $quote = $quotes->findOrFail($id);
        $this->authorize('update', $quote);

        $invoice = $quotes->accept($quote, null, true);

        Flux::toast(variant: 'success', text: __('Quote converted to official invoice.'));

        $this->redirectRoute('admin.invoices.show', ['invoice' => $invoice->id], navigate: true);
    }

    public function confirmDelete(int $id): void
    {
        $this->pendingDeleteId = $id;
        Flux::modal('confirm-delete-quote')->show();
    }

    public function delete(QuotesContract $quotes): void
    {
        if ($this->pendingDeleteId === null) {
            return;
        }

        $quote = $quotes->findOrFail($this->pendingDeleteId);
        $this->authorize('delete', $quote);

        $quotes->delete($this->pendingDeleteId);

        $this->pendingDeleteId = null;
        Flux::modal('confirm-delete-quote')->close();
        Flux::toast(variant: 'success', text: __('Quote proposal deleted.'));
    }

    #[Title('Quotes & Proposals - Travel Billing')]
    public function render(QuotesContract $quotes): View
    {
        return view('travel-invoicing::livewire.quotes.manage-quotes', [
            'quotes' => $quotes->paginate($this->search, $this->statusFilter ?: null, $this->sort),
            'statuses' => QuoteStatus::cases(),
        ])->layout(Layout::admin());
    }
}
