<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Commercial Quotes & Proposals') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create and manage commercial quotes, pricing estimates, and customer proposals.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('admin.invoicing.settings') }}" variant="ghost" icon="cog-6-tooth" wire:navigate>
                {{ __('Settings') }}
            </flux:button>
            <flux:button href="{{ route('admin.quotes.create') }}" variant="primary" icon="plus" wire:navigate>
                {{ __('New Quote') }}
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by reference, title, buyer...') }}" icon="magnifying-glass" />
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
                        <th class="px-6 py-3.5">{{ __('Reference') }}</th>
                        <th class="px-6 py-3.5">{{ __('Client / Buyer') }}</th>
                        <th class="px-6 py-3.5">{{ __('Proposal Title') }}</th>
                        <th class="px-6 py-3.5">{{ __('Total Amount') }}</th>
                        <th class="px-6 py-3.5">{{ __('Deposit') }}</th>
                        <th class="px-6 py-3.5">{{ __('Status') }}</th>
                        <th class="px-6 py-3.5">{{ __('Valid Until') }}</th>
                        <th class="px-6 py-3.5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($quotes as $quote)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="hover:underline text-indigo-600 dark:text-indigo-400" wire:navigate>
                                    {{ $quote->quote_reference }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $quote->buyer_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $quote->buyer_email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-zinc-900 dark:text-white font-medium">{{ $quote->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $quote->items->count() }} {{ __('line items') }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-zinc-900 dark:text-white">
                                {{ $quote->formatted_grand_total }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-zinc-600 dark:text-zinc-300">
                                {{ $quote->formatted_deposit_amount }} <span class="text-zinc-400">({{ $quote->deposit_percent }}%)</span>
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge color="{{ $quote->status->color() }}" size="sm">{{ $quote->status->label() }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 text-xs {{ $quote->isExpired() ? 'text-red-500 font-semibold' : 'text-zinc-500' }}">
                                {{ $quote->valid_until->format('M d, Y') }}
                                @if($quote->isExpired())
                                    <span class="block text-[10px] uppercase tracking-wider text-red-500">{{ __('Expired') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('admin.quotes.show', $quote) }}" size="sm" variant="ghost" icon="eye" wire:navigate />
                                    <flux:dropdown>
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                                        <flux:menu>
                                            <flux:menu.item href="{{ route('admin.quotes.edit', $quote) }}" icon="pencil" wire:navigate>{{ __('Edit Quote') }}</flux:menu.item>
                                            <flux:menu.item href="{{ route('travel-invoicing.pdf.quote', $quote->public_token) }}" target="_blank" icon="arrow-down-tray">{{ __('View PDF') }}</flux:menu.item>
                                            <flux:menu.item href="{{ $quote->publicUrl() }}" target="_blank" icon="arrow-top-right-on-square">{{ __('Client Portal Link') }}</flux:menu.item>
                                            @if($quote->status !== \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Accepted)
                                                <flux:menu.item wire:click="convertToInvoice({{ $quote->id }})" icon="document-check">{{ __('Convert to Invoice') }}</flux:menu.item>
                                            @endif
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="confirmDelete({{ $quote->id }})" variant="danger" icon="trash">{{ __('Delete') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No quotes or proposals found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotes->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                {{ $quotes->links() }}
            </div>
        @endif
    </div>

    <!-- Confirm Delete Modal -->
    <flux:modal name="confirm-delete-quote" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete Proposal Quote?') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to delete this quote proposal? This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
