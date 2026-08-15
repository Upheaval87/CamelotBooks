<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Payslip Report" />
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Payroll Run</label>
                <x-scoped-search-field
                    name="payroll_run_id"
                    entity="payroll-run"
                    search-url="{{ route('accounting.search.entity', ['entity' => 'payroll-run']) }}"
                    :value="$payrollRunId"
                    :label="request('payroll_run_id') ? ($runsList->firstWhere('id', (int) request('payroll_run_id'))?->run_number ?? '') : ''"
                    placeholder="Search payroll runs..."
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <x-scoped-search-field
                    name="employee_id"
                    entity="employee"
                    search-url="{{ route('accounting.search.entity', ['entity' => 'employee']) }}"
                    :value="request('employee_id')"
                    :label="request('employee_id') ? ($employeesList->firstWhere('id', (int) request('employee_id'))?->full_name ?? '') : ''"
                    placeholder="Search employees..."
                />
            </div>
            <x-primary-button type="submit">Filter</x-primary-button>
        </form>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Employee</th>
                <th>Period</th>
                <th class="text-right">Basic ({{ $cs }})</th>
                <th class="text-right">Allowances ({{ $cs }})</th>
                <th class="text-right">PAYE ({{ $cs }})</th>
                <th class="text-right">Pension EE ({{ $cs }})</th>
                <th class="text-right">Total Ded ({{ $cs }})</th>
                <th class="text-right">Net Pay ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $i)
                <tr class="hover:bg-gray-50">
                    <td>{{ $i['employee_name'] }}</td>
                    <td>{{ $i['period_label'] }}</td>
                    <td class="numeric">{{ format_number($i['basic_pay']) }}</td>
                    <td class="numeric">{{ format_number($i['total_allowances']) }}</td>
                    <td class="figure px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['paye']) }}</td>
                    <td class="figure px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['pension_ee']) }}</td>
                    <td class="figure px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['total_deductions']) }}</td>
                    <td class="figure px-4 py-2 text-sm text-right font-bold text-green-700">{{ format_number($i['net_pay']) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No payslips found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@php $totalNetPay = collect($items)->sum('net_pay'); @endphp
    <div class="mt-4 bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Total Net Pay: <span class="text-lg font-bold text-green-700">{{ $cs }} {{ format_number($totalNetPay) }}</span></p>
    </div>
</div>
</x-app-layout>
