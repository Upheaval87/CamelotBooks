@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="header">
    <div class="report-title">Inventory Valuation Report</div>
    <div class="period">As of: {{ now()->format('M d, Y') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Stock Keeping Unit (SKU)</th>
            <th>Product</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Avg Unit Cost ({{ $cs }})</th>
            <th class="text-right">Total Value ({{ $cs }})</th>
            <th class="text-right">% of Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($valuation as $row)
            <tr>
                <td>{{ $row['sku'] ?? '—' }}</td>
                <td>{{ $row['product_name'] }}</td>
                <td class="text-right">{{ format_number($row['total_quantity']) }}</td>
                <td class="text-right">{{ format_number((float)$row['avg_cost'], 4) }}</td>
                <td class="text-right">{{ format_number((float)$row['total_value']) }}</td>
                <td class="text-right">
                    {{ $totalValue > 0 ? number_format(((float)$row['total_value'] / $totalValue) * 100, 1) . '%' : '0.0%' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No inventory items found.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($valuation) > 0)
        <tfoot>
            <tr class="row-grand">
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right">{{ format_number($totalValue) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
    @endif
</table>
