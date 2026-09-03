<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; margin: 0; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 25px; }
        .logo { font-size: 20px; font-weight: bold; color: #0f172a; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .invoice-title p { margin: 2px 0 0; color: #64748b; font-size: 12px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
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
        .grand-total { font-size: 15px; font-weight: bold; color: #0f172a; border-top: 2px solid #e2e8f0; }
        .balance-due { font-size: 16px; font-weight: bold; color: #dc2626; border-top: 1px solid #e2e8f0; }
        .bank-details { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 20px; font-size: 12px; }
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
        <div class="invoice-title">
            <h1>TAX INVOICE</h1>
            <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</p>
            @if($invoice->due_date)
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
            @endif
            @if($invoice->isFullyPaid())
                <div class="status-badge status-paid">PAID IN FULL</div>
            @elseif($invoice->isOverdue())
                <div class="status-badge status-overdue">OVERDUE</div>
            @else
                <div class="status-badge status-pending">PAYMENT PENDING</div>
            @endif
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <h3>BILLED TO</h3>
            <strong>{{ $invoice->buyer_name }}</strong><br>
            {{ $invoice->buyer_email }}<br>
            @if($invoice->buyer_phone) {{ $invoice->buyer_phone }}<br> @endif
            @if($invoice->buyer_address) {{ $invoice->buyer_address }}<br> @endif
            @if($invoice->buyer_country) {{ $invoice->buyer_country }} @endif
        </div>
        <div class="col">
            <h3>DESCRIPTION / ITINERARY</h3>
            <strong>{{ $invoice->title ?: 'Travel & Trekking Services' }}</strong><br>
            @if($invoice->quote)
                <span style="color: #64748b;">Source Proposal: {{ $invoice->quote->quote_reference }}</span><br>
            @endif
            <span style="color: #64748b;">Currency: {{ $invoice->currency }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item / Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Rate</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
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
            <td class="text-right font-mono">{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->subtotal_cents, $invoice->currency) }}</td>
        </tr>
        @if($invoice->tax_cents > 0)
            <tr>
                <td>Tax / VAT:</td>
                <td class="text-right font-mono">+{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->tax_cents, $invoice->currency) }}</td>
            </tr>
        @endif
        @if($invoice->discount_amount_cents > 0)
            <tr style="color: #059669;">
                <td>Discount:</td>
                <td class="text-right font-mono">-{{ \Kreetancraft\TravelInvoicing\Support\MoneyFormatter::formatCents($invoice->discount_amount_cents, $invoice->currency) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Grand Total:</td>
            <td class="text-right font-mono">{{ $invoice->formatted_grand_total }}</td>
        </tr>
        <tr>
            <td>Amount Paid:</td>
            <td class="text-right font-mono" style="color: #059669;">{{ $invoice->formatted_amount_paid }}</td>
        </tr>
        <tr class="balance-due">
            <td>Balance Due:</td>
            <td class="text-right font-mono">{{ $invoice->formatted_balance_due }}</td>
        </tr>
    </table>

    @if($settings->bank_account_details)
        <div class="bank-details">
            <strong>Wire Transfer Payment Instructions:</strong><br>
            {!! nl2br(e($settings->bank_account_details)) !!}
        </div>
    @endif

    @if($settings->payment_terms_notes || $invoice->notes)
        <div class="bank-details" style="background: #fff; border-style: dashed;">
            <strong>Payment Terms & Notes:</strong><br>
            {!! nl2br(e($invoice->notes ?: $settings->payment_terms_notes)) !!}
        </div>
    @endif

    <div class="footer">
        {{ $settings->website ?? 'https://himalayantrek.com' }} &middot; Official Tax Invoice
    </div>
</body>
</html>
