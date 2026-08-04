<x-app-layout>
    <x-list-header title="{{ __('Sales by Terminal') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6 mb-6">
                <form method="GET" action="{{ route('pos.reports.sales-by-terminal') }}" class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}"
                            class="input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}"
                            class="input">
                    </div>
                    <x-button variant="primary" type="submit">Filter</x-button>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Terminal</th>
                                <th class="text-right">Sales Count</th>
                                <th class="text-right">Gross Sales</th>
                                <th class="text-right">Returns</th>
                                <th class="text-right">Net Sales</th>
                                <th class="text-right">Avg Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['terminals'] as $row)
                                <tr>
                                    <td>
                                        {{ $row['terminal']->identifier ?? '—' }} – {{ $row['terminal']->name }}
                                    </td>
                                    <td class="numeric">{{ $row['sales_count'] }}</td>
                                    <td class="numeric">@money($row['sales_total'])</td>
                                    <td class="numeric text-red-600">@money($row['returns_total'])</td>
                                    <td class="numeric font-semibold">@money($row['net_sales'])</td>
                                    <td class="numeric text-ink-soft">@money($row['average_sale'])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-ink-soft text-center">No terminals found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($data['terminals']) > 0)
                        <tfoot class="font-semibold">
                            <tr>
                                <td class="text-ink-soft">Grand Total</td>
                                <td class="numeric">{{ $data['grand_count'] }}</td>
                                <td class="numeric">@money($data['grand_total_sales'])</td>
                                <td class="numeric text-red-600">@money($data['grand_total_returns'])</td>
                                <td class="numeric text-indigo-700">@money($data['grand_net_sales'])</td>
                                <td class="numeric text-ink-soft">
                                    @money($data['grand_count'] > 0 ? $data['grand_total_sales'] / $data['grand_count'] : 0)
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
