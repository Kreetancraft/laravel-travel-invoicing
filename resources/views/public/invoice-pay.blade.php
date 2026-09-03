<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-50 dark:bg-zinc-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} - Billing Portal</title>
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-zinc-900 dark:text-zinc-100 flex flex-col justify-between p-4 sm:p-8">
    <div class="max-w-3xl mx-auto w-full space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 dark:border-zinc-800 pb-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Invoice for') }} {{ $invoice->buyer_name }}</span>
                <h1 class="text-2xl font-bold tracking-tight">{{ $invoice->title ?: __('Tax Invoice') }}</h1>
            </div>
            <div class="mt-2 sm:mt-0 font-mono text-sm text-zinc-500">
                {{ $invoice->invoice_number }}
            </div>
        </div>

        <!-- Status Banner -->
        @if($invoice->isFullyPaid())
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300">
                <div class="font-bold">{{ __('Invoice Paid in Full! ✅') }}</div>
                <div class="text-sm mt-1">{{ __('This invoice has been settled. Thank you for your business!') }}</div>
            </div>
        @elseif($invoice->isOverdue())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300">
                <div class="font-bold">{{ __('Payment Overdue') }}</div>
                <div class="text-sm mt-1">{{ __('Payment was due on ') }} {{ $invoice->due_date->format('M d, Y') }}. {{ __('Please arrange settlement as soon as possible.') }}</div>
            </div>
        @endif

        <!-- Card Container -->
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900 shadow-sm space-y-6">
            <!-- Line Items Table -->
            <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-100 dark:border-zinc-800 text-xs uppercase text-zinc-400">
                    <tr>
                        <th class="pb-3">{{ __('Item / Description') }}</th>
                        <th class="pb-3 text-center">{{ __('Qty') }}</th>
                        <th class="pb-3 text-right">{{ __('Rate') }}</th>
                        <th class="pb-3 text-right">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="py-4 font-medium text-zinc-900 dark:text-white">{{ $item->description }}</td>
                            <td class="py-4 text-center font-mono">{{ $item->quantity }}</td>
                            <td class="py-4 text-right font-mono">{{ $item->formatted_unit_price }}</td>
                            <td class="py-4 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $item->formatted_total_price }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-zinc-100 dark:border-zinc-800 text-sm">
                    <tr>
                        <td colspan="3" class="pt-4 text-right text-zinc-500">{{ __('Subtotal:') }}</td>
                        <td class="pt-4 text-right font-mono font-semibold">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->subtotal_cents, $invoice->currency) }}</td>
                    </tr>
                    @if($invoice->tax_cents > 0)
                        <tr>
                            <td colspan="3" class="pt-2 text-right text-zinc-500">{{ __('Tax / VAT:') }}</td>
                            <td class="pt-2 text-right font-mono">+{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->tax_cents, $invoice->currency) }}</td>
                        </tr>
                    @endif
                    @if($invoice->discount_amount_cents > 0)
                        <tr>
                            <td colspan="3" class="pt-2 text-right text-emerald-600">{{ __('Discount:') }}</td>
                            <td class="pt-2 text-right font-mono text-emerald-600">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->discount_amount_cents, $invoice->currency) }}</td>
                        </tr>
                    @endif
                    <tr class="text-base font-bold text-zinc-900 dark:text-white border-t border-zinc-100 dark:border-zinc-800">
                        <td colspan="3" class="pt-4 text-right">{{ __('Grand Total:') }}</td>
                        <td class="pt-4 text-right font-mono text-lg">{{ $invoice->formatted_grand_total }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="pt-2 text-right text-emerald-600">{{ __('Amount Paid:') }}</td>
                        <td class="pt-2 text-right font-mono text-emerald-600">{{ $invoice->formatted_amount_paid }}</td>
                    </tr>
                    <tr class="text-base font-bold {{ $invoice->balanceDueCents() > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600' }}">
                        <td colspan="3" class="pt-2 text-right">{{ __('Balance Due:') }}</td>
                        <td class="pt-2 text-right font-mono text-lg">{{ $invoice->formatted_balance_due }}</td>
                    </tr>
                </tfoot>
            </table>

            {{--
                Somewhere to actually pay. This page is named `invoice-pay` and
                had no way to pay from it — a customer who accepted a quote saw
                what they owed and was left to arrange a transfer by hand.

                Shown only when the host has wired a gateway, and only while
                something is outstanding. The deposit is offered separately while
                it is genuinely a smaller first step.
            --}}
            @php
                $balanceUrl = \Kreetancraft\TravelInvoicing\Support\CheckoutLink::for($invoice, 'balance');
                $depositUrl = \Kreetancraft\TravelInvoicing\Support\CheckoutLink::for($invoice, 'deposit');
                $offersDeposit = \Kreetancraft\TravelInvoicing\Support\CheckoutLink::offersDeposit($invoice);
                $depositCents = \Kreetancraft\TravelInvoicing\Support\CheckoutLink::amountCentsFor($invoice, 'deposit');
            @endphp

            @if ($balanceUrl)
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-950/40">
                    <div class="text-xs font-bold uppercase tracking-wider text-indigo-500">{{ __('Pay online') }}</div>

                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                        @if ($offersDeposit && $depositUrl)
                            <a href="{{ $depositUrl }}" class="flex-1 rounded-lg bg-indigo-600 px-5 py-3 text-center text-sm font-semibold text-white shadow-xs transition-colors hover:bg-indigo-700">
                                {{ __('Pay deposit — :amount', [
                                    'amount' => \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($depositCents, $invoice->currency),
                                ]) }}
                            </a>
                        @endif

                        <a href="{{ $balanceUrl }}" @class([
                            'flex-1 rounded-lg px-5 py-3 text-center text-sm font-semibold transition-colors',
                            'bg-indigo-600 text-white shadow-xs hover:bg-indigo-700' => ! $offersDeposit,
                            'border border-indigo-300 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/40' => $offersDeposit,
                        ])>
                            {{ $offersDeposit
                                ? __('Pay in full — :amount', ['amount' => $invoice->formatted_balance_due])
                                : __('Pay :amount', ['amount' => $invoice->formatted_balance_due]) }}
                        </a>
                    </div>

                    @if ($offersDeposit)
                        <p class="mt-3 text-xs text-indigo-700 dark:text-indigo-300">
                            {{ __('Paying the deposit secures your booking. The balance stays payable from this same page.') }}
                        </p>
                    @endif
                </div>
            @endif

            <!-- Bank Wire Details (from InvoicingSettings) -->
            @if($settings && $settings->bank_account_details)
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 text-sm space-y-1">
                    <div class="font-bold text-xs uppercase tracking-wider text-zinc-500">{{ __('Bank Wire Transfer Instructions') }}</div>
                    <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-line mt-2">{!! nl2br(e($settings->bank_account_details)) !!}</div>
                </div>
            @endif

            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4 flex items-center justify-between">
                <a href="{{ route('travel-invoicing.pdf.invoice', $invoice->public_token) }}" target="_blank" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 underline">
                    {{ __('Download Official Tax Invoice (PDF)') }}
                </a>
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
