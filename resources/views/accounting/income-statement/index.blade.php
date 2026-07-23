<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Income Statement') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.income-statement.export-csv', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export CSV') }}
                </a>
                <a href="{{ route('accounting.income-statement.export-pdf', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.income-statement.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('Date From') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$dateFrom" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('Date To') }}" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$dateTo" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="compare_mode" value="{{ __('Comparison') }}" />
                        <select id="compare_mode" name="compare_mode" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">No Comparison</option>
                            <option value="prior_period" {{ ($compareMode ?? '') === 'prior_period' ? 'selected' : '' }}>Prior Period</option>
                            <option value="year_ago" {{ ($compareMode ?? '') === 'year_ago' ? 'selected' : '' }}>Year Ago</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.income-statement.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Income Statement: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                @if(!empty($comparison))
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Compare Amount</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $hasIncome = false; @endphp
                            @foreach($groups['income'] as $subType => $items)
                                @if(!empty($items))
                                    @php $hasIncome = true; @endphp
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-6 py-2 text-sm font-semibold text-gray-700">{{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    </tr>
                                    @foreach($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format(max(0, $item['net']), 2) }}</td>
                                            @if(!empty($comparison))
                                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                                    @php
                                                        $compNet = 0;
                                                        foreach (($comparison['groups']['income'][$subType] ?? []) as $ci) {
                                                            if ($ci['account']->id === $item['account']->id) { $compNet = $ci['net']; break; }
                                                        }
                                                    @endphp
                                                    {{ number_format(max(0, $compNet), 2) }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach

                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Income</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($total_income, 2) }}</td>
                                @if(!empty($comparison))
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-500 text-right">{{ number_format($comparison['total_income'] ?? 0, 2) }}</td>
                                @endif
                            </tr>

                            @php $hasExpense = false; @endphp
                            @foreach($groups['expense'] as $subType => $items)
                                @if(!empty($items))
                                    @php $hasExpense = true; @endphp
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="px-6 py-2 text-sm font-semibold text-gray-700">{{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    </tr>
                                    @foreach($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                                            <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format(max(0, $item['net']), 2) }}</td>
                                            @if(!empty($comparison))
                                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 text-right">
                                                    @php
                                                        $compNet = 0;
                                                        foreach (($comparison['groups']['expense'][$subType] ?? []) as $ci) {
                                                            if ($ci['account']->id === $item['account']->id) { $compNet = $ci['net']; break; }
                                                        }
                                                    @endphp
                                                    {{ number_format(max(0, $compNet), 2) }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach

                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Expenses</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($total_expenses, 2) }}</td>
                                @if(!empty($comparison))
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-500 text-right">{{ number_format($comparison['total_expenses'] ?? 0, 2) }}</td>
                                @endif
                            </tr>

                            <tr class="bg-gray-900">
                                <td class="px-6 py-3 text-sm font-bold text-white">{{ $net_income >= 0 ? 'Net Income' : 'Net Loss' }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-white text-right">{{ number_format(abs($net_income), 2) }}</td>
                                @if(!empty($comparison))
                                    @php $compNI = ($comparison['net_income'] ?? 0); @endphp
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-300 text-right">{{ number_format(abs($compNI), 2) }}</td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>