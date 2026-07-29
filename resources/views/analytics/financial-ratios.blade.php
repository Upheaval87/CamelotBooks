<x-app-layout>
    <x-slot name="header">Financial Ratios</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-report-filters mode="point_in_time" :showBranch="true" :showCostCenter="true" :action="route('analytics.financial-ratios')" />

            @if(isset($data['error']))
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">{{ $data['error'] }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    @foreach($data['ratios'] as $category => $ratios)
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 capitalize">{{ str_replace('_', ' ', $category) }}</h3>
                            <div class="space-y-3">
                                @foreach($ratios as $key => $ratio)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                                        @if($ratio === null)
                                            <span class="text-sm font-medium text-gray-400">N/A</span>
                                        @else
                                            <div class="text-right">
                                                <span class="text-sm font-medium {{ isset($ratio['target']) && $ratio['target'] !== null && ($key !== 'working_capital' && !str_contains($key, 'turnover') && !str_contains($key, 'days') && !str_contains($key, 'dso') && !str_contains($key, 'dpo') && !str_contains($key, 'dio') && !str_contains($key, 'ccc')) ? ($ratio['value'] >= $ratio['target'] ? 'text-green-600' : 'text-red-600') : 'text-gray-900' }}">
                                                    @if(in_array($key, ['gross_margin', 'net_margin', 'roa', 'roe']))
                                                        {{ number_format($ratio['value'] * 100, 1) }}%
                                                    @elseif(isset($ratio['unit']) && $ratio['unit'] === 'days')
                                                        {{ number_format($ratio['value'], 0) }} days
                                                    @elseif($key === 'working_capital')
                                                        @money($ratio['value'])
                                                    @else
                                                        {{ format_money($ratio['value']) }}
                                                    @endif
                                                </span>
                                                @if(isset($ratio['target']) && $ratio['target'] !== null)
                                                    <span class="text-xs text-gray-400">Target: {{ $ratio['target'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($data['summary'] as $key => $value)
                            <div>
                                <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</div>
                                <div class="text-lg font-semibold text-gray-800">@money($value)</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
