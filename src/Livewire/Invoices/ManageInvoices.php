<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Livewire\Invoices;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Kreetancraft\TravelInvoicing\Contracts\InvoicesContract;
use Kreetancraft\TravelInvoicing\Enums\InvoiceStatus;
use Kreetancraft\TravelInvoicing\Layout;
use Kreetancraft\TravelInvoicing\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ManageInvoices extends Component
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
        $this->authorize('viewAny', Invoice::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function issueInvoice(int $id, InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($id);
        $this->authorize('update', $invoice);

        $invoices->issue($invoice);

        Flux::toast(variant: 'success', text: __('Invoice issued and marked as sent.'));
    }

    public function voidInvoice(int $id, InvoicesContract $invoices): void
    {
        $invoice = $invoices->findOrFail($id);
        $this->authorize('update', $invoice);

        $invoices->void($invoice);

        Flux::toast(variant: 'warning', text: __('Invoice marked as void.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->pendingDeleteId = $id;
        Flux::modal('confirm-delete-invoice')->show();
    }

    public function delete(InvoicesContract $invoices): void
    {
        if ($this->pendingDeleteId === null) {
            return;
        }

        $invoice = $invoices->findOrFail($this->pendingDeleteId);
        $this->authorize('delete', $invoice);

        $invoices->delete($this->pendingDeleteId);

        $this->pendingDeleteId = null;
        Flux::modal('confirm-delete-invoice')->close();
        Flux::toast(variant: 'success', text: __('Invoice deleted.'));
    }

    #[Title('Invoices & Bills - Travel Billing')]
    public function render(InvoicesContract $invoices): View
    {
        return view('travel-invoicing::livewire.invoices.manage-invoices', [
            'invoices' => $invoices->paginate($this->search, $this->statusFilter ?: null, $this->sort),
            'statuses' => InvoiceStatus::cases(),
        ])->layout(Layout::admin());
    }
}
