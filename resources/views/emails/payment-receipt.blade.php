<x-mail::message>
# {{ __('Payment received') }}

{{ __('Hello :name,', ['name' => $invoice->buyer_name]) }}

{{ __('Thank you — we have received your payment.') }}

<x-mail::table>
| | |
|:---|---:|
| {{ __('Paid now') }} | **{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($payment->amount_cents, $payment->currency) }}** |
| {{ __('Invoice') }} | {{ $invoice->invoice_number }} |
| {{ __('Invoice total') }} | {{ $invoice->formatted_grand_total }} |
| {{ __('Paid to date') }} | {{ $invoice->formatted_amount_paid }} |
| {{ __('Balance remaining') }} | **{{ $invoice->formatted_balance_due }}** |
</x-mail::table>

@if ($invoice->isFullyPaid())
{{ __('That settles this invoice in full. Nothing further is owed.') }}
@else
{{ __('The balance above is still outstanding. You can pay it whenever you are ready.') }}
@endif

<x-mail::button :url="route('travel-invoicing.public.invoice', $invoice->public_token)">
{{ __('View invoice') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
