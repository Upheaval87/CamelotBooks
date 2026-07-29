<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trial Balance Comparison') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-reports-toolbar :action="route('accounting.reports.trial-balance-comparison')" showCompare />

            <form method="GET" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end flex-wrap">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700">Period 1 From</label><input type="date" name="date_from1" value="{{ $dateFrom1 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Period 1 To</label><input type="date" name="date_to1" value="{{ $dateTo1 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Period 2 From</label><input type="date" name="date_from2" value="{{ $dateFrom2 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700">Period 2 To</label><input type="date" name="date_to2" value="{{ $dateTo2 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
            </form>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dr P1 ({{ $cs }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cr P1 ({{ $cs }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dr P2 ({{ $cs }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cr P2 ({{ $cs }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dr Var ({{ $cs }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cr Var ({{ $cs }})</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">{{ $row['code'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['dr_p1']) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['cr_p1']) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['dr_p2']) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['cr_p2']) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['dr_var']) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ format_number($row['cr_var']) }}</td>
                        </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm text-right">Totals</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_dr_p1) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_cr_p1) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_dr_p2) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_cr_p2) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_dr_var) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ format_number($total_cr_var) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>