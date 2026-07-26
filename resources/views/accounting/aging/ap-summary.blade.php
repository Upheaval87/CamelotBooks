<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('A/P Aging Summary') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ap'])) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Export CSV') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.aging.ap-summary') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$as_of_date" />
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
                        <a href="{{ route('accounting.aging.ap-summary') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 flex gap-2">
                <a href="{{ route('accounting.aging.ap-summary', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm">Summary</a>
                <a href="{{ route('accounting.aging.ap-detail', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Detail</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">A/P Aging Summary as of {{ \Carbon\Carbon::parse($as_of_date)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Current</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">1-30 Days</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">31-60 Days</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">61-90 Days</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">90+ Days</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($vendors as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row['vendor_name'] }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($row['current']) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($row['days_1_30']) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($row['days_31_60']) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($row['days_61_90']) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($row['days_90_plus']) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right">{{ format_money($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No outstanding bills found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['current']) }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['days_1_30']) }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['days_31_60']) }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['days_61_90']) }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['days_90_plus']) }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ format_money($totals['total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>