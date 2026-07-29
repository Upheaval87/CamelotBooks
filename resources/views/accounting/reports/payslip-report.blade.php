<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Payslip Report</h1>
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Payroll Run</label>
                <select name="payroll_run_id" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Runs</option>
                    @foreach($runsList as $r)
                        <option value="{{ $r->id }}" {{ request('payroll_run_id') == $r->id ? 'selected' : '' }}>{{ $r->run_number }} — {{ $r->period_label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <select name="employee_id" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Employees</option>
                    @foreach($employeesList as $e)
                        <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->first_name }} {{ $e->last_name }}</option>
                    @endforeach
                </select>
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
                    <td class="px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['paye']) }}</td>
                    <td class="px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['pension_ee']) }}</td>
                    <td class="px-4 py-2 text-sm text-right text-red-600">{{ format_number($i['total_deductions']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-green-700">{{ format_number($i['net_pay']) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No payslips found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Total Net Pay: <span class="text-lg font-bold text-green-700">{{ $cs }} {{ format_number($total_net_pay) }}</span></p>
    </div>
</div>
</x-app-layout>
