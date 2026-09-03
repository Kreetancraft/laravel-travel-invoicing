<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $quote->quote_reference }} - {{ $quote->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; margin: 0; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px; }
        .logo { font-size: 20px; font-weight: bold; color: #0f172a; }
        .quote-ref { text-align: right; }
        .quote-ref h1 { margin: 0; font-size: 24px; color: #4338ca; }
        .quote-ref p { margin: 2px 0 0; color: #64748b; font-size: 12px; }
        .grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .col { width: 48%; }
        .col h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-top: 0; margin-bottom: 6px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .totals-table { width: 45%; margin-left: auto; margin-bottom: 30px; }
        .totals-table td { padding: 6px 12px; }
        .grand-total { font-size: 16px; font-weight: bold; color: #4338ca; border-top: 2px solid #e2e8f0; }
        .deposit-callout { background: #eef2ff; border-left: 4px solid #6366f1; padding: 12px 16px; border-radius: 4px; margin-bottom: 25px; }
        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 20px; font-size: 12px; }
        .footer { text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo">{{ $settings->business_name ?? 'Himalayan Trek & Tours' }}</div>
            <div style="color: #64748b; font-size: 11px; margin-top: 4px;">
                {{ $settings->tax_id }}<br>
                {{ $settings->address }}<br>
                {{ $settings->email }} &middot; {{ $settings->phone }}
            </div>
        </div>
        <div class="quote-ref">
            <h1>COMMERCIAL PROPOSAL</h1>
            <p><strong>Ref:</strong> {{ $quote->quote_reference }}</p>
            <p><strong>Date:</strong> {{ $quote->created_at->format('M d, Y') }}</p>
            <p><strong>Valid Until:</strong> {{ $quote->valid_until->format('M d, Y') }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h3>PROPOSAL FOR</h3>
            <strong>{{ $quote->buyer_name }}</strong><br>
            {{ $quote->buyer_email }}<br>
            @if($quote->buyer_phone) {{ $quote->buyer_phone }}<br> @endif
            @if($quote->buyer_address) {{ $quote->buyer_address }}<br> @endif
            @if($quote->buyer_country) {{ $quote->buyer_country }} @endif
        </div>
        <div class="col">
            <h3>PACKAGE & ITINERARY</h3>
            <strong>{{ $quote->title }}</strong><br>
            <span style="color: #64748b;">Currency: {{ $quote->currency }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Service / Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->title }}</strong>
                        @if($item->description)
                            <div style="color: #64748b; font-size: 11px;">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $item->quantity }}</td>
                    <td class="text-right font-mono">{{ $item->formatted_unit_price }}</td>
                    <td class="text-right font-mono font-bold">{{ $item->formatted_total_price }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right font-mono">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->subtotal_cents, $quote->currency) }}</td>
        </tr>
        @if($quote->discount_amount_cents > 0)
            <tr style="color: #059669;">
                <td>Discount / Promo:</td>
                <td class="text-right font-mono">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($quote->discount_amount_cents, $quote->currency) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Grand Total:</td>
            <td class="text-right font-mono">{{ $quote->formatted_grand_total }}</td>
        </tr>
    </table>

    <div class="deposit-callout">
        <strong>Booking Deposit Required:</strong>
        <span class="font-mono">{{ $quote->formatted_deposit_amount }} ({{ $quote->deposit_percent }}%)</span> — Required upon proposal acceptance to lock in trek permits and logistics.
    </div>

    @if($quote->client_notes)
        <div class="notes-box">
            <strong>Notes & Inclusions:</strong><br>
            {!! nl2br(e($quote->client_notes)) !!}
        </div>
    @endif

    <div class="footer">
        {{ $settings->website ?? 'https://himalayantrek.com' }} &middot; Thank you for traveling with us!
    </div>
</body>
</html>
