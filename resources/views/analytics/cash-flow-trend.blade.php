<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cash Flow Trend & Projection</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                    ['label' => 'Historical Operating', 'data' => array_merge($data['operating'], array_fill(0, $data['projection_count'], null)), 'borderColor' => '#6366f1', 'backgroundColor' => 'rgba(99,102,241,0.1)', 'fill' => true],
                    ['label' => 'Projected Operating', 'data' => array_merge(array_fill(0, $data['historical_count'], null), $data['projection_operating']), 'borderColor' => '#6366f1', 'borderDash' => [5,5], 'backgroundColor' => 'transparent', 'fill' => false],
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

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    <div>
                        <p class="text-sm font-medium text-amber-800">Projection Disclaimer</p>
                        <p class="text-sm text-amber-700 mt-1">{{ $data['projection_note'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Operating</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Investing</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Financing</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @for($i = 0; $i < count($data['labels']); $i++)
                                @php
                                    $isProjection = $i >= $data['historical_count'];
                                    $operating = $isProjection ? ($data['projection_operating'][$i - $data['historical_count']] ?? 0) : ($data['operating'][$i] ?? 0);
                                    $investing = $isProjection ? ($data['projection_investing'][$i - $data['historical_count']] ?? 0) : ($data['investing'][$i] ?? 0);
                                    $financing = $isProjection ? ($data['projection_financing'][$i - $data['historical_count']] ?? 0) : ($data['financing'][$i] ?? 0);
                                    $net = $isProjection ? ($data['projection_net'][$i - $data['historical_count']] ?? 0) : ($data['net'][$i] ?? 0);
                                @endphp
                                <tr class="{{ $isProjection ? 'bg-gray-50' : '' }}">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $data['labels'][$i] }}
                                        @if($isProjection)
                                            <span class="text-xs text-amber-600 ml-1">(Projected)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($operating, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($investing, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($financing, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format($net, 2) }}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
