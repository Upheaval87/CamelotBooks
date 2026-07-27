<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt {{ $salesReceipt->receipt_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; max-width: 400px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .meta-table { width: 100%; margin-bottom: 15px; }
        .meta-table td { padding: 2px 0; }
        .meta-table .label { font-weight: bold; width: 80px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th, .items-table td { padding: 4px; text-align: left; font-size: 11px; }
        .items-table th { border-bottom: 1px solid #333; text-transform: uppercase; font-size: 10px; }
        .items-table td:nth-child(n+3) { text-align: right; }
        .totals { margin-top: 10px; text-align: right; }
        .totals table { float: right; }
        .totals td { padding: 2px 8px; }
        .totals .grand-total { border-top: 1px solid #333; padding-top: 4px; font-weight: bold; }
        .payments { margin-top: 15px; border-top: 1px dashed #999; padding-top: 10px; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>SALES RECEIPT</h1>
        <p><strong>{{ $salesReceipt->receipt_number }}</strong></p>
        <p>{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</p>
    </div>

    <table class="meta-table">
        <tr><td class="label">Customer:</td><td>{{ $salesReceipt->customer->name ?? 'Walk-in' }}</td></tr>
        @if($salesReceipt->reference)
            <tr><td class="label">Reference:</td><td>{{ $salesReceipt->reference }}</td></tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($salesReceipt->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td style="text-align: center">{{ number_format($line->quantity, 0) }}</td>
                    <td>{{ format_money($line->unit_price) }}</td>
                    <td>{{ format_money($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal:</td><td>{{ format_money($salesReceipt->subtotal) }}</td></tr>
            @if($salesReceipt->tax_total > 0)
                <tr><td>Tax:</td><td>{{ format_money($salesReceipt->tax_total) }}</td></tr>
            @endif
            <tr class="grand-total"><td>Total:</td><td>{{ format_money($salesReceipt->total) }}</td></tr>
        </table>
    </div>

    <div class="payments">
        <strong>Payments:</strong>
        @foreach($salesReceipt->payments as $payment)
            <div>{{ $payment->paymentMethod->name ?? '—' }}: {{ format_money($payment->amount) }}</div>
        @endforeach
    </div>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p>Generated on {{ now()->format('M d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
