<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Payroll Run') }} #{{ $run->run_number }}
                </h2>
                @php
                    $statusColors = [
                        'draft' => 'gray',
                        'calculated' => 'yellow',
                        'approved' => 'blue',
                        'posted' => 'green',
                        'partially_paid' => 'orange',
                        'fully_paid' => 'emerald',
                    ];
                    $color = $statusColors[$run->status] ?? 'gray';
                @endphp
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                    {{ str_replace('_', ' ', ucfirst($run->status)) }}
                </span>
            </div>
            <a href="{{ route('accounting.payroll-runs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Payroll Runs') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Run Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->run_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Period') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->period_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Period Start') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->period_start?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Period End') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->period_end?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Pay Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->pay_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                {{ str_replace('_', ' ', ucfirst($run->status)) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Employees') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $run->items->count() }}</dd>
                    </div>
                </div>

                <div class="mt-6 flex items-center space-x-3">
                    @if($run->status === 'calculated')
                        <form method="POST" action="{{ route('accounting.payroll-runs.approve', $run) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Are you sure you want to approve this payroll run?')">
                                {{ __('Approve Run') }}
                            </button>
                        </form>
                    @endif

                    @if($run->status === 'approved')
                        <form method="POST" action="{{ route('accounting.payroll-runs.post', $run) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Are you sure you want to post this payroll run to the General Ledger?')">
                                {{ __('Post to GL') }}
                            </button>
                        </form>
                    @endif

                    @if(in_array($run->status, ['posted', 'partially_paid', 'fully_paid']))
                        <form method="POST" action="{{ route('accounting.payroll-runs.send-payslips', $run) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Send payslips to all employees?')">
                                {{ __('Send Payslips') }}
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('accounting.payroll-runs.print-payslips', $run) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" target="_blank">
                        {{ __('Print Payslips') }}
                    </a>
                    <a href="{{ route('accounting.payroll-runs.paye-schedule', $run) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" target="_blank">
                        {{ __('PAYE Schedule') }}
                    </a>
                    <a href="{{ route('accounting.payroll-runs.pension-schedule', $run) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" target="_blank">
                        {{ __('Pension Schedule') }}
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Gross Pay') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-900">{{ format_money($run->total_gross) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Total PAYE') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-red-600">{{ format_money($run->total_paye) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Total Pension') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-orange-600">{{ format_money($run->total_pension) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Total Deductions') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-yellow-600">{{ format_money($run->total_deductions) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Net Pay') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-green-600">{{ format_money($run->total_net_pay) }}</dd>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Employee Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Pay</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PAYE</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pension EE</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($run->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->employee->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ format_money($item->basic_pay) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ format_money($item->allowances) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                        {{ format_money($item->gross_pay) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">
                                        {{ format_money($item->paye) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 text-right">
                                        {{ format_money($item->pension_ee) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 text-right">
                                        {{ format_money($item->other_deductions) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right font-bold">
                                        {{ format_money($item->net_pay) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        @if(in_array($run->status, ['posted', 'partially_paid', 'fully_paid']) && !$item->is_paid)
                                            <button type="button" @click="openPaymentModal({{ $item->id }}, '{{ addslashes($item->employee->name ?? '') }}', {{ $item->net_pay }})" class="text-green-600 hover:text-green-900">
                                                {{ __('Pay') }}
                                            </button>
                                        @endif
                                        <a href="{{ route('accounting.payroll-runs.print-payslips', $run) }}?employee={{ $item->employee_id }}" class="text-gray-600 hover:text-gray-900" target="_blank">
                                            {{ __('Payslip') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No employee items found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ showPayeModal: false, showPensionModal: false }">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Remittances') }}</h3>
                <div class="flex items-center space-x-3 mb-4">
                    <button type="button" @click="showPayeModal = true" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Record PAYE Remittance') }}
                    </button>
                    <button type="button" @click="showPensionModal = true" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 focus:bg-orange-500 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Record Pension Remittance') }}
                    </button>
                </div>

                <div x-show="showPayeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showPayeModal = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Record PAYE Remittance') }}</h3>
                            <form method="POST" action="{{ route('accounting.payroll-runs.remit-paye', $run) }}">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="paye_amount" value="{{ __('Amount') }}" />
                                        <x-text-input id="paye_amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', $run->total_paye)" required />
                                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="paye_date" value="{{ __('Payment Date') }}" />
                                        <x-text-input id="paye_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="paye_bank_account_id" value="{{ __('Bank Account') }}" />
                                        <select id="paye_bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" @click="showPayeModal = false" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Cancel') }}
                                    </button>
                                    <x-primary-button type="submit">{{ __('Record Remittance') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div x-show="showPensionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showPensionModal = false"></div>
                        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Record Pension Remittance') }}</h3>
                            <form method="POST" action="{{ route('accounting.payroll-runs.remit-pension', $run) }}">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="pension_ee_amount" value="{{ __('Employee Pension Amount') }}" />
                                        <x-text-input id="pension_ee_amount" name="employee_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('employee_amount')" required />
                                        <x-input-error :messages="$errors->get('employee_amount')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="pension_er_amount" value="{{ __('Employer Pension Amount') }}" />
                                        <x-text-input id="pension_er_amount" name="employer_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('employer_amount')" required />
                                        <x-input-error :messages="$errors->get('employer_amount')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="pension_date" value="{{ __('Payment Date') }}" />
                                        <x-text-input id="pension_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="pension_bank_account_id" value="{{ __('Bank Account') }}" />
                                        <select id="pension_bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" @click="showPensionModal = false" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('Cancel') }}
                                    </button>
                                    <x-primary-button type="submit">{{ __('Record Remittance') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($payments) && $payments->count())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment History') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Account</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($payments as $payment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $payment->payrollRunItem->employee->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            {{ format_money($payment->amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $payment->payment_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $payment->bankAccount->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ ucfirst($payment->status ?? 'completed') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                            No payments recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if(isset($deliveries) && $deliveries->count())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Delivery Status') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($deliveries as $delivery)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $delivery->payrollRunItem->employee->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @php
                                                $deliveryStatusColors = [
                                                    'pending' => 'gray',
                                                    'sent' => 'blue',
                                                    'delivered' => 'green',
                                                    'failed' => 'red',
                                                ];
                                                $dColor = $deliveryStatusColors[$delivery->status] ?? 'gray';
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $dColor }}-100 text-{{ $dColor }}-800">
                                                {{ ucfirst($delivery->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $delivery->sent_at?->format('M d, Y H:i') ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-red-600">
                                            {{ $delivery->error ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                            No deliveries recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div x-data="paymentModal()" x-cloak style="display: none;">
        <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="close()"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        {{ __('Pay Employee') }} — <span x-text="employeeName"></span>
                    </h3>
                    <form method="POST" action="{{ route('accounting.payroll-runs.pay-employee', $run) }}">
                        @csrf
                        <input type="hidden" name="payroll_run_item_id" :value="itemId" />
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="payment_amount" value="{{ __('Amount') }}" />
                                <x-text-input id="payment_amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="amount" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="payment_payment_date" value="{{ __('Payment Date') }}" />
                                <x-text-input id="payment_payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="new Date().toISOString().split('T')[0]" required />
                                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="payment_bank_account_id" value="{{ __('Bank Account') }}" />
                                <select id="payment_bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}">
                                            {{ $bankAccount->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="close()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </button>
                            <x-primary-button type="submit">{{ __('Record Payment') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function paymentModal() {
        return {
            open: false,
            itemId: null,
            employeeName: '',
            amount: 0,
            openPaymentModal(id, name, netPay) {
                this.itemId = id;
                this.employeeName = name;
                this.amount = netPay;
                this.open = true;
            },
            close() {
                this.open = false;
            }
        };
    }
</script>
