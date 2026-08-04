<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Payroll Summary" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Run #</th>
                <th>Period</th>
                <th>Pay Date</th>
                <th class="text-right">Gross ({{ $cs }})</th>
                <th class="text-right">PAYE ({{ $cs }})</th>
                <th class="text-right">Pension EE ({{ $cs }})</th>
                <th class="text-right">Pension ER ({{ $cs }})</th>
                <th class="text-right">Deductions ({{ $cs }})</th>
                <th class="text-right">Net Pay ({{ $cs }})</th>
                <th class="text-right">Employer Cost ({{ $cs }})</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['run_number'] }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td>{{ $row['pay_date'] }}</td>
                    <td class="numeric">{{ format_number($row['gross']) }}</td>
                    <td class="numeric">{{ format_number($row['paye']) }}</td>
                    <td class="numeric">{{ format_number($row['pension_ee']) }}</td>
                    <td class="numeric">{{ format_number($row['pension_er']) }}</td>
                    <td class="numeric">{{ format_number($row['deductions']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['net_pay']) }}</td>
                    <td class="numeric">{{ format_number($row['employer_cost']) }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
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
    <x-list-header title="Payroll Summary" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Run #</th>
                <th>Period</th>
                <th>Pay Date</th>
                <th class="text-right">Gross ({{ $cs }})</th>
                <th class="text-right">PAYE ({{ $cs }})</th>
                <th class="text-right">Pension EE ({{ $cs }})</th>
                <th class="text-right">Pension ER ({{ $cs }})</th>
                <th class="text-right">Deductions ({{ $cs }})</th>
                <th class="text-right">Net Pay ({{ $cs }})</th>
                <th class="text-right">Employer Cost ({{ $cs }})</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['run_number'] }}</td>
                    <td>{{ $row['period'] }}</td>
                    <td>{{ $row['pay_date'] }}</td>
                    <td class="numeric">{{ format_number($row['gross']) }}</td>
                    <td class="numeric">{{ format_number($row['paye']) }}</td>
                    <td class="numeric">{{ format_number($row['pension_ee']) }}</td>
                    <td class="numeric">{{ format_number($row['pension_er']) }}</td>
                    <td class="numeric">{{ format_number($row['deductions']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['net_pay']) }}</td>
                    <td class="numeric">{{ format_number($row['employer_cost']) }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-8 text-center text-sm text-gray-500">No payroll runs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>