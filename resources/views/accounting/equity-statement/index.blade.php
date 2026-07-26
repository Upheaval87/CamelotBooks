<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Statement of Changes in Equity') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.equity-statement.export-csv', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export CSV') }}
                </a>
                <a href="{{ route('accounting.equity-statement.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.equity-statement.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('From Date') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$dateFrom" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('To Date') }}" />
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
                        <a href="{{ route('accounting.equity-statement.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Statement of Changes in Equity<br>
                        <span class="text-sm font-normal text-gray-600">
                            {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                        </span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Movement</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($movements as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-gray-900">
                                        {{ $item['account']->code }} — {{ $item['account']->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-900 text-right">
                                        {{ number_format($item['opening'], 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right {{ $item['movement'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $item['movement'] >= 0 ? '+' : '' }}{{ number_format($item['movement'], 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium">
                                        {{ number_format($item['closing'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No equity accounts found.</td>
                                </tr>
                            @endforelse

                            <tr class="bg-blue-50">
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">Net Income for Period</td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right"></td>
                                <td class="px-6 py-3 text-sm text-right font-semibold {{ $net_income >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $net_income >= 0 ? '+' : '' }}{{ number_format($net_income, 2) }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right"></td>
                            </tr>

                            <tr class="bg-gray-100 font-bold">
                                <td class="px-6 py-3 text-sm text-gray-900">Total Equity</td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right">{{ number_format($total_opening, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right">
                                    {{ ($total_closing - $total_opening) >= 0 ? '+' : '' }}{{ number_format($total_closing - $total_opening, 2) }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right">{{ number_format($total_closing, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
