<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-50 dark:bg-zinc-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->quote_reference }} - Commercial Proposal</title>
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
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Proposal for') }} {{ $quote->buyer_name }}</span>
                <h1 class="text-2xl font-bold tracking-tight">{{ $quote->title }}</h1>
            </div>
            <div class="mt-2 sm:mt-0 font-mono text-sm text-zinc-500">
                {{ $quote->quote_reference }}
            </div>
        </div>

        <!-- Proposal Status Banner -->
        @if($quote->status === \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Accepted)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300">
                <div class="font-bold">{{ __('Proposal Accepted! 🎉') }}</div>
                <div class="text-sm mt-1">{{ __('Thank you! Our operations team has received your confirmation and will follow up shortly.') }}</div>
            </div>
        @elseif($quote->status === \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Rejected)
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300">
                <div class="font-bold">{{ __('Proposal Declined') }}</div>
                <div class="text-sm mt-1">{{ __('This proposal was marked as declined. Please contact your travel specialist if you wish to revise it.') }}</div>
            </div>
        @elseif($quote->isExpired())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300">
                <div class="font-bold">{{ __('Proposal Expired') }}</div>
                <div class="text-sm mt-1">{{ __('This proposal validity period ended on ') }} {{ $quote->valid_until->format('M d, Y') }}.</div>
            </div>
        @endif

        <!-- Card Container -->
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900 shadow-sm space-y-6">
            <!-- Items Table -->
            <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-100 dark:border-zinc-800 text-xs uppercase text-zinc-400">
                    <tr>
                        <th class="pb-3">{{ __('Description') }}</th>
                        <th class="pb-3 text-center">{{ __('Qty') }}</th>
                        <th class="pb-3 text-right">{{ __('Rate') }}</th>
                        <th class="pb-3 text-right">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($quote->items as $item)
                        <tr>
                            <td class="py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $item->title }}</div>
                                @if($item->description)
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="py-4 text-center font-mono">{{ $item->quantity }}</td>
                            <td class="py-4 text-right font-mono">{{ $item->formatted_unit_price }}</td>
                            <td class="py-4 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $item->formatted_total_price }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-zinc-100 dark:border-zinc-800 text-sm">
                    <tr>
                        <td colspan="3" class="pt-4 text-right text-zinc-500">{{ __('Subtotal:') }}</td>
                        <td class="pt-4 text-right font-mono font-semibold">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->subtotal_cents, $quote->currency) }}</td>
                    </tr>
                    @if($quote->discount_amount_cents > 0)
                        <tr>
                            <td colspan="3" class="pt-2 text-right text-emerald-600">{{ __('Discount:') }}</td>
                            <td class="pt-2 text-right font-mono text-emerald-600">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->discount_amount_cents, $quote->currency) }}</td>
                        </tr>
                    @endif
                    <tr class="text-base font-bold text-zinc-900 dark:text-white border-t border-zinc-100 dark:border-zinc-800">
                        <td colspan="3" class="pt-4 text-right">{{ __('Grand Total:') }}</td>
                        <td class="pt-4 text-right font-mono text-lg text-indigo-600 dark:text-indigo-400">{{ $quote->formatted_grand_total }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Deposit Milestone Box -->
            <div class="rounded-xl bg-indigo-50/70 p-4 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 text-sm text-indigo-900 dark:text-indigo-200 flex items-center justify-between">
                <div>
                    <div class="font-bold">{{ __('Booking Deposit (Upon Acceptance):') }}</div>
                    <div class="text-xs text-indigo-700 dark:text-indigo-300">{{ __('Locks in reservations, guides, and trekking permits.') }}</div>
                </div>
                <div class="font-mono font-bold text-base">{{ $quote->formatted_deposit_amount }} ({{ $quote->deposit_percent }}%)</div>
            </div>

            <!-- Notes -->
            @if($quote->client_notes)
                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">{{ __('Inclusions & Important Notes') }}</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300 whitespace-pre-line">{{ $quote->client_notes }}</p>
                </div>
            @endif

            <!-- Response Actions (if pending) -->
            @if(in_array($quote->status, [\Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Sent, \Kreetancraft\TravelInvoicing\Enums\QuoteStatus::Draft]))
                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="{{ route('travel-invoicing.pdf.quote', $quote->public_token) }}" target="_blank" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 underline">
                        {{ __('Download Official PDF Proposal') }}
                    </a>
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('travel-invoicing.public.quote.accept', $quote->public_token) }}">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-xs transition-colors">
                                {{ __('Accept & Confirm Proposal') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @livewireScripts
</body>
</html>
