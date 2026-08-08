<x-app-layout>
    <x-list-header title="Cash Flow Trend & Projection" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('analytics.cash-flow-trend') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="date_from" value="From" />
                        <x-text-input id="date_from" name="date_from" type="date" :value="$dateFrom" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_to" value="To" />
                        <x-text-input id="date_to" name="date_to" type="date" :value="$dateTo" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="projection_months" value="Projection Months" />
                        <x-text-input id="projection_months" name="projection_months" type="number" :value="$projectionMonths" class="mt-1 block w-full" min="1" max="24" />
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Apply</x-primary-button>
                    </div>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Net Cash Flow Trend</h3>
                <x-chart type="line" :id="'cash-flow-trend'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                    ['label' => 'Historical Operating', 'data' => array_merge($data['operating'], array_fill(0, $data['projection_count'], null)), 'borderColor' => '#128F8E', 'backgroundColor' => 'rgba(18,143,142,0.1)', 'fill' => true],
                    ['label' => 'Projected Operating', 'data' => array_merge(array_fill(0, $data['historical_count'], null), $data['projection_operating']), 'borderColor' => '#128F8E', 'borderDash' => [5,5], 'backgroundColor' => 'transparent', 'fill' => false],
                    ['label' => 'Historical Investing', 'data' => array_merge($data['investing'], array_fill(0, $data['projection_count'], null)), 'borderColor' => '#f59e0b', 'backgroundColor' => 'transparent', 'fill' => false],
                    ['label' => 'Projected Investing', 'data' => array_merge(array_fill(0, $data['historical_count'], null), $data['projection_investing']), 'borderColor' => '#f59e0b', 'borderDash' => [5,5], 'backgroundColor' => 'transparent', 'fill' => false],
                    ['label' => 'Historical Financing', 'data' => array_merge($data['financing'], array_fill(0, $data['projection_count'], null)), 'borderColor' => '#10b981', 'backgroundColor' => 'transparent', 'fill' => false],
                    ['label' => 'Projected Financing', 'data' => array_merge(array_fill(0, $data['historical_count'], null), $data['projection_financing']), 'borderColor' => '#10b981', 'borderDash' => [5,5], 'backgroundColor' => 'transparent', 'fill' => false],
                ])" height="400" />
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Net Cash Flow (Combined)</h3>
                <x-chart type="bar" :id="'net-cash-flow'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                    ['label' => 'Historical Net', 'data' => array_merge($data['net'], array_fill(0, $data['projection_count'], null)), 'backgroundColor' => array_merge(array_map(fn($v) => $v >= 0 ? '#10b981' : '#ef4444', $data['net']), array_fill(0, $data['projection_count'], '#9ca3af'))],
                    ['label' => 'Projected Net', 'data' => array_merge(array_fill(0, $data['historical_count'], null), $data['projection_net']), 'backgroundColor' => '#d1d5db', 'borderDash' => [5,5]],
                ])" height="300" />
            </div>

            <x-feedback.alert variant="warning" title="Projection Disclaimer" class="mb-4">{{ $data['projection_note'] }}</x-feedback.alert>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-right">Operating</th>
                                <th class="text-right">Investing</th>
                                <th class="text-right">Financing</th>
                                <th class="text-right">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < count($data['labels']); $i++)
                                @php
                                    $isProjection = $i >= $data['historical_count'];
                                    $operating = $isProjection ? ($data['projection_operating'][$i - $data['historical_count']] ?? 0) : ($data['operating'][$i] ?? 0);
                                    $investing = $isProjection ? ($data['projection_investing'][$i - $data['historical_count']] ?? 0) : ($data['investing'][$i] ?? 0);
                                    $financing = $isProjection ? ($data['projection_financing'][$i - $data['historical_count']] ?? 0) : ($data['financing'][$i] ?? 0);
                                    $net = $isProjection ? ($data['projection_net'][$i - $data['historical_count']] ?? 0) : ($data['net'][$i] ?? 0);
                                @endphp
                                <tr class="{{ $isProjection ? 'text-ink-soft' : '' }}">
                                    <td>
                                        {{ $data['labels'][$i] }}
                                        @if($isProjection)
                                            <span class="text-xs text-amber-600 ml-1">(Projected)</span>
                                        @endif
                                    </td>
                                    <td class="numeric">@money($operating)</td>
                                    <td class="numeric">@money($investing)</td>
                                    <td class="numeric">@money($financing)</td>
                                    <td class="numeric font-medium {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">@money($net)</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
