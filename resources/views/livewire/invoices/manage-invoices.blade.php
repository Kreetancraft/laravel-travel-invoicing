<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Invoices & Billing') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage customer tax invoices, deposit payments, remaining balances, and payment records.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('admin.invoicing.settings') }}" variant="ghost" icon="cog-6-tooth" wire:navigate>
                {{ __('Settings') }}
            </flux:button>
            <flux:button href="{{ route('admin.invoices.create') }}" variant="primary" icon="plus" wire:navigate>
                {{ __('New Invoice') }}
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by invoice #, buyer, email...') }}" icon="magnifying-glass" />
        </div>
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3.5">{{ __('Invoice #') }}</th>
                        <th class="px-6 py-3.5">{{ __('Client / Buyer') }}</th>
                        <th class="px-6 py-3.5">{{ __('Total') }}</th>
                        <th class="px-6 py-3.5">{{ __('Paid') }}</th>
                        <th class="px-6 py-3.5">{{ __('Balance Due') }}</th>
                        <th class="px-6 py-3.5">{{ __('Status') }}</th>
                        <th class="px-6 py-3.5">{{ __('Due Date') }}</th>
                        <th class="px-6 py-3.5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="hover:underline text-indigo-600 dark:text-indigo-400" wire:navigate>
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $invoice->buyer_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $invoice->buyer_email }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-zinc-900 dark:text-white font-mono">
                                {{ $invoice->formatted_grand_total }}
                            </td>
                            <td class="px-6 py-4 font-mono text-emerald-600 dark:text-emerald-400 text-xs">
                                {{ $invoice->formatted_amount_paid }}
                            </td>
                            <td class="px-6 py-4 font-mono font-semibold text-zinc-900 dark:text-white text-xs">
                                {{ $invoice->formatted_balance_due }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge color="{{ $invoice->status->color() }}" size="sm">{{ $invoice->status->label() }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 text-xs {{ $invoice->isOverdue() ? 'text-red-500 font-semibold' : 'text-zinc-500' }}">
                                {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '-' }}
                                @if($invoice->isOverdue())
                                    <span class="block text-[10px] uppercase tracking-wider text-red-500">{{ __('Overdue') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('admin.invoices.show', $invoice) }}" size="sm" variant="ghost" icon="eye" wire:navigate />
                                    <flux:dropdown>
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item href="{{ route('admin.invoices.edit', $invoice) }}" icon="pencil" wire:navigate>{{ __('Edit Invoice') }}</flux:menu.item>
                                            <flux:menu.item href="{{ route('travel-invoicing.pdf.invoice', $invoice->public_token) }}" target="_blank" icon="arrow-down-tray">{{ __('Download PDF') }}</flux:menu.item>
                                            <flux:menu.item href="{{ $invoice->publicUrl() }}" target="_blank" icon="arrow-top-right-on-square">{{ __('Client Payment Portal') }}</flux:menu.item>
                                            @if($invoice->status === \Kreetancraft\TravelInvoicing\Enums\InvoiceStatus::Draft)
                                                <flux:menu.item wire:click="issueInvoice({{ $invoice->id }})" icon="paper-airplane">{{ __('Issue Invoice') }}</flux:menu.item>
                                            @endif
                                            @if($invoice->status !== \Kreetancraft\TravelInvoicing\Enums\InvoiceStatus::Void)
                                                <flux:menu.item wire:click="voidInvoice({{ $invoice->id }})" icon="x-circle">{{ __('Mark as Void') }}</flux:menu.item>
                                            @endif
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="confirmDelete({{ $invoice->id }})" variant="danger" icon="trash">{{ __('Delete') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No invoices found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <!-- Confirm Delete Modal -->
    <flux:modal name="confirm-delete-invoice" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete Invoice?') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to delete this invoice record? This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
