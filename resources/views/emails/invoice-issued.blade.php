<x-mail::message>
# {{ __('Invoice :number', ['number' => $invoice->invoice_number]) }}

{{ __('Hello :name,', ['name' => $invoice->buyer_name]) }}

@if ($invoice->title)
{{ __('Here is your invoice for :title.', ['title' => $invoice->title]) }}
@else
{{ __('Here is your invoice.') }}
@endif

<x-mail::table>
| | |
|:---|---:|
| {{ __('Total') }} | **{{ $invoice->formatted_grand_total }}** |
@if ($invoice->deposit_amount_cents > 0)
| {{ __('Deposit due on acceptance') }} | {{ $invoice->formatted_deposit_amount }} |
@endif
@if ($invoice->due_date)
| {{ __('Due') }} | {{ $invoice->due_date->format('M j, Y') }} |
@endif
</x-mail::table>

<x-mail::button :url="route('travel-invoicing.public.invoice', $invoice->public_token)">
{{ __('View invoice') }}
</x-mail::button>

{{ __('This link is yours alone — it opens the invoice without a password.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
