<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white font-mono">{{ $quote->quote_reference }}</h1>
                <flux:badge color="{{ $quote->status->color() }}">{{ $quote->status->label() }}</flux:badge>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $quote->title }} &middot; {{ __('Created on') }} {{ $quote->created_at->format('M d, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.quotes.edit', $quote) }}" variant="ghost" icon="pencil" wire:navigate>{{ __('Edit Quote') }}</flux:button>
            <flux:button href="{{ route('travel-invoicing.pdf.quote', $quote->public_token) }}" target="_blank" variant="ghost" icon="arrow-down-tray">{{ __('PDF') }}</flux:button>
            <flux:button href="{{ $quote->publicUrl() }}" target="_blank" variant="ghost" icon="arrow-top-right-on-square">{{ __('Client Portal') }}</flux:button>
            @if($quote->status === \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Draft)
                <flux:button wire:click="sendQuote" variant="primary" icon="paper-airplane">{{ __('Send to Client') }}</flux:button>
            @endif
            @if($quote->status !== \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Accepted)
                <flux:button wire:click="convertToInvoice" variant="primary" icon="document-check">{{ __('Convert to Invoice') }}</flux:button>
            @endif
        </div>
    </div>

    <!-- Client & Proposal Overview -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Client Snapshot -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Client Information') }}</h2>
            <div class="space-y-1">
                <div class="font-medium text-zinc-900 dark:text-white text-base">{{ $quote->buyer_name }}</div>
                <div class="text-sm text-zinc-500">{{ $quote->buyer_email }}</div>
                @if($quote->buyer_phone)
                    <div class="text-sm text-zinc-500">{{ $quote->buyer_phone }}</div>
                @endif
                @if($quote->buyer_country)
                    <div class="text-xs text-zinc-400 mt-1">{{ $quote->buyer_country }}</div>
                @endif
            </div>
        </div>

        <!-- Proposal Terms & Dates -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Proposal Validity & Terms') }}</h2>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-zinc-500">{{ __('Valid Until:') }}</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $quote->valid_until->format('M d, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">{{ __('Booking Deposit:') }}</span> <span class="font-medium text-zinc-900 dark:text-white">{{ $quote->formatted_deposit_amount }} ({{ $quote->deposit_percent }}%)</span></div>
                @if($quote->sent_at)
                    <div class="flex justify-between"><span class="text-zinc-500">{{ __('Sent to Client:') }}</span> <span class="text-zinc-600 dark:text-zinc-300">{{ $quote->sent_at->format('M d, Y H:i') }}</span></div>
                @endif
                @if($quote->responded_at)
                    <div class="flex justify-between"><span class="text-zinc-500">{{ __('Responded:') }}</span> <span class="text-zinc-600 dark:text-zinc-300">{{ $quote->responded_at->format('M d, Y H:i') }}</span></div>
                @endif
            </div>
        </div>

        <!-- Generated Invoice Link (if converted) -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Billing Status') }}</h2>
            @if($quote->invoice)
                <div class="space-y-2">
                    <p class="text-xs text-zinc-500">{{ __('This quote has been converted into an official tax invoice:') }}</p>
                    <a href="{{ route('admin.invoices.show', $quote->invoice) }}" class="inline-flex items-center gap-2 font-mono font-semibold text-indigo-600 dark:text-indigo-400 hover:underline" wire:navigate>
                        {{ $quote->invoice->invoice_number }} &rarr;
                    </a>
                </div>
            @else
                <p class="text-xs text-zinc-500">{{ __('No invoice generated yet. Click "Convert to Invoice" to generate billing schedules.') }}</p>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-xs">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Itemized Pricing & Services') }}</h2>
        </div>
        <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3.5">{{ __('Service / Item') }}</th>
                    <th class="px-6 py-3.5 text-center">{{ __('Qty') }}</th>
                    <th class="px-6 py-3.5 text-right">{{ __('Rate') }}</th>
                    <th class="px-6 py-3.5 text-right">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($quote->items as $item)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $item->title }}</div>
                            @if($item->description)
                                <div class="text-xs text-zinc-500">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center font-mono">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-right font-mono">{{ $item->formatted_unit_price }}</td>
                        <td class="px-6 py-4 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $item->formatted_total_price }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-zinc-200 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-800/30 text-sm">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right font-medium text-zinc-500">{{ __('Subtotal:') }}</td>
                    <td class="px-6 py-3 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->subtotal_cents, $quote->currency) }}</td>
                </tr>
                @if($quote->discount_amount_cents > 0)
                    <tr>
                        <td colspan="3" class="px-6 py-2 text-right text-emerald-600 dark:text-emerald-400">{{ __('Discount / Promo:') }}</td>
                        <td class="px-6 py-2 text-right font-mono text-emerald-600 dark:text-emerald-400">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->discount_amount_cents, $quote->currency) }}</td>
                    </tr>
                @endif
                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                    <td colspan="3" class="px-6 py-4 text-right text-base font-bold text-zinc-900 dark:text-white">{{ __('Grand Total:') }}</td>
                    <td class="px-6 py-4 text-right font-mono text-lg font-bold text-zinc-900 dark:text-white">{{ $quote->formatted_grand_total }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Notes & Comments -->
    @if($quote->client_notes || $quote->internal_notes)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @if($quote->client_notes)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Client Notes & Special Requests') }}</h3>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line">{{ $quote->client_notes }}</p>
                </div>
            @endif
            @if($quote->internal_notes)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Internal Staff Notes') }}</h3>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line">{{ $quote->internal_notes }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
