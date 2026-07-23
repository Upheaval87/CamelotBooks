<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Cash Flow Statement') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.cash-flow.export-csv', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export CSV') }}
                </a>
                <a href="{{ route('accounting.cash-flow.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.cash-flow.index') }}" class="flex items-end gap-4">
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
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.cash-flow.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Cash Flow Statement: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">Operating Activities</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">Net Income</td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($net_income, 2) }}</td>
                            </tr>
                            @foreach($non_cash_expenses['items'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">Add: {{ $item['account']->name }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            @foreach($sections['operating'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item['cash_effect'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Net Cash from Operating</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($operating_total, 2) }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">Investing Activities</td>
                            </tr>
                            @forelse($sections['investing'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item['cash_effect'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-2 text-sm text-gray-500 pl-10">No investing activities</td>
                                    <td class="px-6 py-2 text-right text-sm text-gray-500">{{ number_format(0, 2) }}</td>
                                </tr>
                            @endforelse
                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Net Cash from Investing</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($investing_total, 2) }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">Financing Activities</td>
                            </tr>
                            @forelse($sections['financing'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 pl-10">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item['cash_effect'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-2 text-sm text-gray-500 pl-10">No financing activities</td>
                                    <td class="px-6 py-2 text-right text-sm text-gray-500">{{ number_format(0, 2) }}</td>
                                </tr>
                            @endforelse
                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Net Cash from Financing</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($financing_total, 2) }}</td>
                            </tr>

                            <tr class="bg-gray-900">
                                <td class="px-6 py-3 text-sm font-bold text-white">Net Change in Cash</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-white text-right">{{ number_format($net_change, 2) }}</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm text-gray-900">Beginning Cash Balance</td>
                                <td class="px-6 py-2 text-right text-sm text-gray-900">{{ number_format($beginning_cash, 2) }}</td>
                            </tr>
                            <tr class="bg-indigo-50">
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Ending Cash Balance</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ number_format($ending_cash, 2) }}</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm text-gray-500">Actual Ending Cash</td>
                                <td class="px-6 py-2 text-right text-sm {{ $mismatch ? 'text-red-600 font-semibold' : 'text-gray-500' }}">{{ number_format($actual_ending_cash, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($mismatch)
                    <div class="px-6 py-3 bg-red-50 border-t border-red-200">
                        <p class="text-sm font-semibold text-red-600">Warning: Ending cash does not match actual bank balances. Difference: {{ number_format($mismatch, 2) }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>