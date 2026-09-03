<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white font-mono">{{ $invoice->invoice_number }}</h1>
                <flux:badge color="{{ $invoice->status->color() }}">{{ $invoice->status->label() }}</flux:badge>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $invoice->title ?: __('Tax Invoice') }} &middot; {{ __('Issued on') }} {{ $invoice->issue_date->format('M d, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.invoices.edit', $invoice) }}" variant="ghost" icon="pencil" wire:navigate>{{ __('Edit') }}</flux:button>
            <flux:button href="{{ route('travel-invoicing.pdf.invoice', $invoice->public_token) }}" target="_blank" variant="ghost" icon="arrow-down-tray">{{ __('PDF') }}</flux:button>
            <flux:button href="{{ $invoice->publicUrl() }}" target="_blank" variant="ghost" icon="arrow-top-right-on-square">{{ __('Payment Portal') }}</flux:button>
            @if($invoice->balanceDueCents() > 0 && $invoice->status !== \Kreetancraft\TravelInvoicing\Enums\InvoiceStatus::Void)
                <flux:button wire:click="openRecordPaymentModal" variant="primary" icon="banknotes">{{ __('Record Payment') }}</flux:button>
            @endif
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Client Details -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-2">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Billed To') }}</h2>
            <div class="font-medium text-zinc-900 dark:text-white text-base">{{ $invoice->buyer_name }}</div>
            <div class="text-sm text-zinc-500">{{ $invoice->buyer_email }}</div>
            @if($invoice->buyer_phone)
                <div class="text-sm text-zinc-500">{{ $invoice->buyer_phone }}</div>
            @endif
            @if($invoice->buyer_country)
                <div class="text-xs text-zinc-400 mt-1">{{ $invoice->buyer_country }}</div>
            @endif
        </div>

        <!-- Payment Breakdown -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-2 text-sm">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Payment Breakdown') }}</h2>
            <div class="flex justify-between"><span class="text-zinc-500">{{ __('Total Amount:') }}</span> <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $invoice->formatted_grand_total }}</span></div>
            <div class="flex justify-between"><span class="text-zinc-500">{{ __('Amount Paid:') }}</span> <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $invoice->formatted_amount_paid }}</span></div>
            <div class="flex justify-between border-t border-zinc-100 dark:border-zinc-800 pt-2"><span class="font-bold text-zinc-700 dark:text-zinc-300">{{ __('Balance Due:') }}</span> <span class="font-mono font-bold text-base {{ $invoice->balanceDueCents() > 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $invoice->formatted_balance_due }}</span></div>
        </div>

        <!-- Dates & Quote Reference -->
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs space-y-2 text-sm">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Invoice Dates') }}</h2>
            <div class="flex justify-between"><span class="text-zinc-500">{{ __('Issue Date:') }}</span> <span>{{ $invoice->issue_date->format('M d, Y') }}</span></div>
            <div class="flex justify-between"><span class="text-zinc-500">{{ __('Due Date:') }}</span> <span class="{{ $invoice->isOverdue() ? 'text-red-500 font-semibold' : '' }}">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '-' }}</span></div>
            @if($invoice->quote)
                <div class="flex justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800"><span class="text-zinc-500">{{ __('Source Proposal:') }}</span> <a href="{{ route('admin.quotes.show', $invoice->quote) }}" class="font-mono text-indigo-600 hover:underline" wire:navigate>{{ $invoice->quote->quote_reference }}</a></div>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-xs">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Billed Services & Items') }}</h2>
        </div>
        <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3.5">{{ __('Description') }}</th>
                    <th class="px-6 py-3.5 text-center">{{ __('Qty') }}</th>
                    <th class="px-6 py-3.5 text-right">{{ __('Unit Rate') }}</th>
                    <th class="px-6 py-3.5 text-right">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $item->description }}</td>
                        <td class="px-6 py-4 text-center font-mono">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-right font-mono">{{ $item->formatted_unit_price }}</td>
                        <td class="px-6 py-4 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $item->formatted_total_price }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-zinc-200 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-800/30 text-sm">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right font-medium text-zinc-500">{{ __('Subtotal:') }}</td>
                    <td class="px-6 py-3 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->subtotal_cents, $invoice->currency) }}</td>
                </tr>
                @if($invoice->tax_cents > 0)
                    <tr>
                        <td colspan="3" class="px-6 py-2 text-right text-zinc-500">{{ __('Tax / VAT:') }}</td>
                        <td class="px-6 py-2 text-right font-mono text-zinc-900 dark:text-white">+{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->tax_cents, $invoice->currency) }}</td>
                    </tr>
                @endif
                @if($invoice->discount_amount_cents > 0)
                    <tr>
                        <td colspan="3" class="px-6 py-2 text-right text-emerald-600 dark:text-emerald-400">{{ __('Discount:') }}</td>
                        <td class="px-6 py-2 text-right font-mono text-emerald-600 dark:text-emerald-400">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->discount_amount_cents, $invoice->currency) }}</td>
                    </tr>
                @endif
                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                    <td colspan="3" class="px-6 py-4 text-right text-base font-bold text-zinc-900 dark:text-white">{{ __('Grand Total:') }}</td>
                    <td class="px-6 py-4 text-right font-mono text-lg font-bold text-zinc-900 dark:text-white">{{ $invoice->formatted_grand_total }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payments Ledger Table -->
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-xs">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Recorded Payments & Transactions') }}</h2>
            @if($invoice->balanceDueCents() > 0 && $invoice->status !== \Kreetancraft\TravelInvoicing\Enums\InvoiceStatus::Void)
                <flux:button wire:click="openRecordPaymentModal" size="sm" variant="ghost" icon="plus">{{ __('Record Payment') }}</flux:button>
            @endif
        </div>
        <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3.5">{{ __('Date') }}</th>
                    <th class="px-6 py-3.5">{{ __('Gateway / Method') }}</th>
                    <th class="px-6 py-3.5">{{ __('Reference') }}</th>
                    <th class="px-6 py-3.5">{{ __('Notes') }}</th>
                    <th class="px-6 py-3.5 text-right">{{ __('Amount Paid') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($invoice->payments as $payment)
                    <tr>
                        <td class="px-6 py-4 text-xs font-mono text-zinc-500">{{ $payment->paid_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 font-medium uppercase text-xs">{{ $payment->gateway ?: 'manual' }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $payment->transaction_reference ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs text-zinc-500">{{ $payment->notes ?: '-' }}</td>
                        <td class="px-6 py-4 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ $payment->formatted_amount }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-500">{{ __('No payments recorded yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Record Payment Modal -->
    <flux:modal name="record-payment-modal" class="max-w-md">
        <form wire:submit.prevent="recordPayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Record Payment for ') }} {{ $invoice->invoice_number }}</flux:heading>
            
            {{--
                The amount as it appears on the invoice. This asked for cents, so
                recording $3,240.00 meant typing 324000 — arithmetic in your head
                and a number three orders of magnitude larger than the one you
                are looking at. One slip is a payment a hundred times too big.
            --}}
            <flux:input
                wire:model="paymentAmount"
                label="{{ __('Amount (:currency)', ['currency' => strtoupper($invoice->currency)]) }}"
                type="number"
                step="0.01"
                min="0.01"
                max="{{ number_format($invoice->balanceDueCents() / 100, 2, '.', '') }}"
                required
            />
            <p class="text-xs text-zinc-500">{{ __('Outstanding: ') }}<strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ $invoice->formatted_balance_due }}</strong></p>

            {{--
                Manual methods only. Stripe and the bank record themselves when
                their webhook lands, so offering them here invites a payment with
                no gateway reference — which the real webhook then records again.
            --}}
            <flux:select wire:model.live="paymentGateway" label="{{ __('How was it paid?') }}" required>
                @foreach ($this->paymentMethods() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="paymentReference"
                label="{{ __('Reference') }}"
                description="{{ __('Generated for you. Replace it with the bank slip or cheque number if you have one.') }}"
            />
            <flux:textarea wire:model="paymentNotes" label="{{ __('Payment Notes') }}" placeholder="Optional notes..." rows="2" />

            <div class="flex justify-end gap-3 pt-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Confirm Payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
