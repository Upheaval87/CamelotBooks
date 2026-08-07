<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt – {{ $sale->sale_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 13px; color: #000; background: #fff; }
        .receipt { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-t { border-top: 1px dashed #000; margin: 8px 0; padding-top: 8px; }
        .mb-2 { margin-bottom: 8px; }
        .flex-between { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; }
        th { text-align: left; font-weight: normal; }
        .line-total { text-align: right; }
        @media print {
            body { font-size: 11px; }
            .no-print { display: none; }
            .receipt { border: none; max-width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="text-center mb-2">
            <div class="font-bold" style="font-size: 16px;">POS RECEIPT</div>
            <div>Sale: {{ $sale->sale_number }}</div>
            <div>{{ $sale->created_at->format('M d, Y H:i') }}</div>
            @if($sale->terminal)
                <div>Terminal: {{ $sale->terminal->identifier }}</div>
            @endif
        </div>

        @if($sale->customer)
            <div class="border-t">
                <div class="font-bold">Customer:</div>
                <div>{{ $sale->customer->name }}</div>
            </div>
        @endif

        <div class="border-t">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="line-total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->lines as $line)
                        <tr>
                            <td>{{ $line->product->name ?? 'N/A' }}</td>
                            <td class="text-right">{{ $line->quantity }}</td>
                            <td class="text-right">@money($line->unit_price)</td>
                            <td class="line-total">@money($line->line_total)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t">
            <div class="flex-between"><span>Subtotal:</span><span>@money($sale->subtotal)</span></div>
            @if($sale->discount_total > 0)
                <div class="flex-between"><span>Discount:</span><span>-@money($sale->discount_total)</span></div>
            @endif
            @if($sale->tax_total > 0)
                <div class="flex-between"><span>Tax:</span><span>@money($sale->tax_total)</span></div>
            @endif
            <div class="flex-between font-bold" style="font-size: 15px; margin-top: 4px;">
                <span>TOTAL:</span><span>@money($sale->total)</span>
            </div>
        </div>

        <div class="border-t">
            <div class="font-bold mb-2">Payments:</div>
            @foreach($sale->payments as $payment)
                <div class="flex-between">
                    <span>{{ $payment->paymentMethod->name ?? 'N/A' }}</span>
                    <span>@money($payment->amount)</span>
                </div>
            @endforeach
        </div>

        <div class="text-center border-t" style="margin-top: 16px; padding-top: 8px;">
            <div>Thank you for your purchase!</div>
        </div>

        <div class="text-center no-print" style="margin-top: 20px;">
            <button onclick="window.print()" class="px-4 py-2 bg-gold-600 text-white rounded hover:bg-gold-800">Print Receipt</button>
            <a href="{{ route('pos.dashboard') }}" class="ml-2 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Back to POS</a>
        </div>
    </div>
</body>
</html>
