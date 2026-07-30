<x-app-layout>
    <x-slot name="header">{{ __('Inventory Valuation Report') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-end gap-2 mb-4">
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.by-category') }}">{{ __('By Category') }}</x-button>
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-csv') }}">{{ __('Export CSV') }}</x-button>
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-pdf') }}" target="_blank">{{ __('Print / PDF') }}</x-button>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Inventory Valuation"
                    :range="'As of ' . now()->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.inventory-valuation.export-csv')"
                />

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
                                <td colspan="6" class="text-center text-ink-soft" style="padding:20px 14px">No inventory items found.</td>
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
            </x-report.card>
        </div>
    </div>
</x-app-layout>
