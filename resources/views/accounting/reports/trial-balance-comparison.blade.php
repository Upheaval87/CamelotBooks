<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $total_dr_p1 = $total_debit_1;
        $total_cr_p1 = $total_credit_1;
        $total_dr_p2 = $total_debit_2;
        $total_cr_p2 = $total_credit_2;
        $total_dr_var = $total_debit_2 - $total_debit_1;
        $total_cr_var = $total_credit_2 - $total_credit_1;
    @endphp
    <x-list-header title="{{ __('Trial Balance Comparison') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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
                <table class="datasheet">
                    <thead><tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th class="text-right">Dr P1 ({{ $cs }})</th>
                        <th class="text-right">Cr P1 ({{ $cs }})</th>
                        <th class="text-right">Dr P2 ({{ $cs }})</th>
                        <th class="text-right">Cr P2 ({{ $cs }})</th>
                        <th class="text-right">Dr Var ({{ $cs }})</th>
                        <th class="text-right">Cr Var ({{ $cs }})</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($lines as $row)
                        <tr class="hover:bg-gray-50">
                            <td>{{ $row['account']->code }}</td>
                            <td>{{ $row['account']->name }}</td>
                            <td class="numeric">{{ format_number($row['debit_1']) }}</td>
                            <td class="numeric">{{ format_number($row['credit_1']) }}</td>
                            <td class="numeric">{{ format_number($row['debit_2']) }}</td>
                            <td class="numeric">{{ format_number($row['credit_2']) }}</td>
                            <td class="numeric">{{ format_number($row['variance_debit']) }}</td>
                            <td class="numeric">{{ format_number($row['variance_credit']) }}</td>
                        </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm text-right">Totals</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_dr_p1) }}</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_cr_p1) }}</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_dr_p2) }}</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_cr_p2) }}</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_dr_var) }}</td>
                            <td class="figure px-4 py-3 text-sm text-right">{{ format_number($total_cr_var) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>