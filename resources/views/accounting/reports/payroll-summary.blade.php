<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Payroll Summary</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Run #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pay Date</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PAYE ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension EE ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension ER ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deductions ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Pay ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Employer Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['run_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['period'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['pay_date'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['gross']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['paye']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pension_ee']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pension_er']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['deductions']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['net_pay']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['employer_cost']) }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst($row['status']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-8 text-center text-sm text-gray-500">No payroll runs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Payroll Summary</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Run #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pay Date</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">PAYE ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension EE ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pension ER ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deductions ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Pay ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Employer Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['run_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['period'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['pay_date'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['gross']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['paye']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pension_ee']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pension_er']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['deductions']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['net_pay']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['employer_cost']) }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst($row['status']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-8 text-center text-sm text-gray-500">No payroll runs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>