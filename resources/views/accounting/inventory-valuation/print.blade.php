@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ config('app.name') }}</p>
    <p class="report-title">Inventory Valuation</p>
    <p class="report-range">As of {{ now()->format('M d, Y') }}</p>
</div>

<div class="report-toolbar">
    <label class="zero-toggle">
        <input type="checkbox" id="reportZeroToggle" checked onchange="toggleZeroRows()">
        Show zero-value items
    </label>
    <button class="btn-outline" onclick="window.print()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print
    </button>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Stock Keeping Unit (SKU)</th>
            <th>Product</th>
            <th class="report-col-amt">Quantity</th>
            <th class="report-col-amt">Avg Unit Cost ({{ $cs }})</th>
            <th class="report-col-amt">Total Value ({{ $cs }})</th>
            <th class="report-col-amt">% of Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($valuation as $row)
            @php $zero = (float)$row['total_value'] <= 0; @endphp
            <tr class="@if($zero) zero @endif">
                <td><span class="report-cell-code">{{ $row['sku'] ?? '—' }}</span></td>
                <td>{{ $row['product_name'] }}</td>
                <td class="report-cell-amt">{{ format_number($row['total_quantity']) }}</td>
                <td class="report-cell-amt">{{ format_number((float)$row['avg_cost'], 4) }}</td>
                <td class="report-cell-amt">{{ format_number((float)$row['total_value']) }}</td>
                <td class="report-cell-amt">
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
            <tr class="report-total-row">
                <td colspan="4" style="text-align:right">Total</td>
                <td class="report-cell-amt report-total-val">{{ format_number($totalValue) }}</td>
                <td class="report-cell-amt report-total-val">100.0%</td>
            </tr>
        </tfoot>
    @endif
</table>
