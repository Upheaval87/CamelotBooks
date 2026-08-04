<x-app-layout>
    <x-list-header title="{{ __('Inventory Valuation by Category') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-end gap-2 mb-4">
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.index') }}">{{ __('Back to Valuation') }}</x-button>
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-csv') }}">{{ __('Export CSV') }}</x-button>
                <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-pdf') }}" target="_blank">{{ __('Print / PDF') }}</x-button>
            </div>

            @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Inventory Valuation by Category"
                    :range="'As of ' . now()->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.inventory-valuation.export-csv')"
                />

                @forelse($categoryData as $category)
                    <x-report.section-bar>
                        <span>
                            <span class="report-cell-code">{{ $category['code'] }}</span>
                            {{ $category['name'] }}
                            <span class="text-ink-soft font-normal text-xs">({{ count($category['products']) }} products)</span>
                        </span>
                        <span class="font-bold">{{ format_number($category['total_value']) }}</span>
                    </x-report.section-bar>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Product</th>
                                <th class="report-col-amt">Quantity</th>
                                <th class="report-col-amt">Avg Unit Cost ({{ $cs }})</th>
                                <th class="report-col-amt">Total Value ({{ $cs }})</th>
                                <th class="report-col-amt">% of Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category['products'] as $product)
                                @php $zero = (float)$product['value'] <= 0; @endphp
                                <tr class="@if($zero) zero @endif">
                                    <td><span class="report-cell-code">{{ $product['sku'] ?? '—' }}</span></td>
                                    <td>{{ $product['name'] }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['quantity']) }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['quantity'] > 0 ? $product['value'] / $product['quantity'] : 0) }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['value']) }}</td>
                                    <td class="report-cell-amt">
                                        {{ $category['total_value'] > 0 ? number_format(($product['value'] / $category['total_value']) * 100, 1) . '%' : '0.0%' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft" style="padding:10px 14px">No products with stock in this category.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @empty
                    <div class="text-center text-ink-soft" style="padding:20px 14px">No item categories found.</div>
                @endforelse

                @if(count($uncategorizedData) > 0)
                    <x-report.section-bar>
                        <span>
                            Uncategorized Products
                            <span class="text-ink-soft font-normal text-xs">({{ count($uncategorizedData) }} products)</span>
                        </span>
                        <span class="font-bold">{{ format_number(array_sum(array_column($uncategorizedData, 'value'))) }}</span>
                    </x-report.section-bar>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Product</th>
                                <th class="report-col-amt">Quantity</th>
                                <th class="report-col-amt">Avg Unit Cost ({{ $cs }})</th>
                                <th class="report-col-amt">Total Value ({{ $cs }})</th>
                                <th class="report-col-amt">% of Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uncategorizedData as $product)
                                @php $zero = (float)$product['value'] <= 0; @endphp
                                <tr class="@if($zero) zero @endif">
                                    <td><span class="report-cell-code">{{ $product['sku'] ?? '—' }}</span></td>
                                    <td>{{ $product['name'] }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['quantity']) }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['quantity'] > 0 ? $product['value'] / $product['quantity'] : 0) }}</td>
                                    <td class="report-cell-amt">{{ format_number($product['value']) }}</td>
                                    <td class="report-cell-amt">—</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if(count($categoryData) > 0 || count($uncategorizedData) > 0)
                    <div class="report-total">
                        <span class="lbl">Grand Total</span>
                        <span class="val">{{ format_number($grandTotal) }}</span>
                    </div>
                @endif
            </x-report.card>
        </div>
    </div>
</x-app-layout>
