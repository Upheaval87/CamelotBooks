<x-app-layout>
    <x-slot name="header">{{ __('Balance Sheet') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.balance-sheet.export-csv', request()->query()) }}">{{ __('Export CSV') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.balance-sheet.export-pdf', request()->query()) }}" target="_blank">{{ __('Export PDF') }}</x-button>
    </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.balance-sheet.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$asOfDate" />
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
                        <a href="{{ route('accounting.balance-sheet.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Balance Sheet as of {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups['asset'] as $subType => $items)
                                @if(!empty($items))
                                    <tr class="bg-gray-50">
                                        <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">{{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    </tr>
                                    @foreach($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td><a href="{{ route('accounting.general-ledger.account', $item['account']->id) }}?date_to={{ $asOfDate }}{{ $branchId ? '&branch_id='.$branchId : '' }}" class="text-ink hover:text-gold underline">{{ $item['account']->code }} - {{ $item['account']->name }}</a></td>
                                            <td class="numeric">{{ format_money($item['balance']) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            <tr class="bg-indigo-50">
                                <td>Total Assets</td>
                                <td class="numeric">{{ format_money($total_assets) }}</td>
                            </tr>

                            @foreach($groups['liability'] as $subType => $items)
                                @if(!empty($items))
                                    <tr class="bg-gray-50">
                                        <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">{{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    </tr>
                                    @foreach($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td><a href="{{ route('accounting.general-ledger.account', $item['account']->id) }}?date_to={{ $asOfDate }}{{ $branchId ? '&branch_id='.$branchId : '' }}" class="text-ink hover:text-gold underline">{{ $item['account']->code }} - {{ $item['account']->name }}</a></td>
                                            <td class="numeric">{{ format_money($item['balance']) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            <tr class="bg-indigo-50">
                                <td>Total Liabilities</td>
                                <td class="numeric">{{ format_money($total_liabilities) }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-6 py-2 text-sm font-semibold text-gray-700">Equity</td>
                            </tr>
                            @foreach($groups['equity'] as $subType => $items)
                                @foreach($items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td><a href="{{ route('accounting.general-ledger.account', $item['account']->id) }}?date_to={{ $asOfDate }}{{ $branchId ? '&branch_id='.$branchId : '' }}" class="text-ink hover:text-gold underline">{{ $item['account']->code }} - {{ $item['account']->name }}</a></td>
                                        <td class="numeric">{{ format_money($item['balance']) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr class="hover:bg-gray-50">
                                <td>Current Year Earnings</td>
                                <td class="numeric">{{ format_money($current_year_earnings) }}</td>
                            </tr>
                            <tr class="bg-indigo-50">
                                <td>Total Equity</td>
                                <td class="numeric">{{ format_money($total_equity) }}</td>
                            </tr>

                            <tr class="bg-gray-900">
                                <td>Total Liabilities & Equity</td>
                                <td class="numeric">{{ format_money($total_liabilities + $total_equity) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if(!$balanced)
                    <div class="px-6 py-3 bg-red-50 border-t border-red-200">
                        <p class="text-sm font-semibold text-red-600">Warning: Balance sheet is out of balance by {{ format_money(abs($total_assets - ($total_liabilities + $total_equity))) }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>