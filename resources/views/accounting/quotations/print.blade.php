<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { padding: 4px 0; }
        .meta-table .label { font-weight: bold; width: 120px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background-color: #f5f5f5; font-size: 11px; text-transform: uppercase; }
        .items-table td:nth-child(n+3) { text-align: right; }
        .totals { float: right; width: 250px; margin-top: 20px; }
        .totals table { width: 100%; }
        .totals td { padding: 4px 8px; }
        .totals td:last-child { text-align: right; font-weight: bold; }
        .totals .grand-total { font-size: 14px; border-top: 2px solid #333; padding-top: 8px; }
        .footer { margin-top: 40px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div><h1>QUOTATION</h1><p><strong>{{ $quotation->quotation_number }}</strong></p></div>
        <div style="text-align: right;"><p>Date: {{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</p>@if($quotation->valid_until)<p>Valid Until: {{ $quotation->valid_until->format('M d, Y') }}</p>@endif<p>Status: {{ ucfirst($quotation->status) }}</p></div>
    </div>
    <table class="meta-table">
        <tr><td class="label">Customer:</td><td>{{ $quotation->customer->name ?? '—' }}</td></tr>
        @if($quotation->reference)<tr><td class="label">Reference:</td><td>{{ $quotation->reference }}</td></tr>@endif
        @if($quotation->memo)<tr><td class="label">Description:</td><td>{{ $quotation->memo }}</td></tr>@endif
    </table>
    <table class="items-table">
        <thead><tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Tax Rate</th><th>Total</th></tr></thead>
        <tbody>@foreach($quotation->lines as $idx => $line)<tr><td>{{ $idx + 1 }}</td><td>{{ $line->description }}</td><td>{{ number_format($line->quantity, 2) }}</td><td>{{ format_money($line->unit_price) }}</td><td>{{ number_format($line->tax_rate, 2) }}%</td><td>{{ format_money($line->line_total) }}</td></tr>@endforeach</tbody>
    </table>
    <div class="totals"><table>
        <tr><td>Subtotal:</td><td>{{ format_money($quotation->amount) }}</td></tr>
        <tr><td>Tax:</td><td>{{ format_money($quotation->tax_total) }}</td></tr>
        <tr class="grand-total"><td>Total:</td><td>{{ format_money($quotation->total) }}</td></tr>
    </table></div>
    <div class="footer"><p>Generated on {{ now()->format('M d, Y \a\t h:i A') }}</p></div>
</body>
</html>
