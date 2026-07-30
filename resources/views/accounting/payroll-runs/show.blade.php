<x-app-layout>
    <x-slot name="header">{{ __('Payroll Run') }} #{{ $run->run_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Run Details') }}</p>
                        <div class="detail-grid">
                            <x-detail-field :label="__('Run Number')" :value="$run->run_number" />
                            <x-detail-field :label="__('Period')" :value="$run->period_label" />
                            <x-detail-field :label="__('Period Start')" :value="$run->period_start?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Period End')" :value="$run->period_end?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Pay Date')" :value="$run->pay_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Status')" noBorder>
                                <span class="status-pill {{ $color }}">{{ str_replace('_', ' ', ucfirst($run->status)) }}</span>
                            </x-detail-field>
                            <x-detail-field :label="__('Employees')" :value="$run->items->count()" />
                        </div>

                        <div class="mt-6 flex items-center space-x-3">
                            @if($run->status === 'calculated')
                                @can('payroll-runs.approve')
                                    <form method="POST" action="{{ route('accounting.payroll-runs.approve', $run) }}">
                                        @csrf
                                        <button type="submit" class="x-button x-button-primary" onclick="return confirm('Are you sure you want to approve this payroll run?')">
                                            {{ __('Approve Run') }}
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if($run->status === 'approved')
                                @can('payroll-runs.post')
                                    <form method="POST" action="{{ route('accounting.payroll-runs.post', $run) }}">
                                        @csrf
                                        <button type="submit" class="x-button x-button-positive" onclick="return confirm('Are you sure you want to post this payroll run to the General Ledger?')">
                                            {{ __('Post to GL') }}
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if(in_array($run->status, ['posted', 'partially_paid', 'fully_paid']))
                                <form method="POST" action="{{ route('accounting.payroll-runs.send-payslips', $run) }}">
                                    @csrf
                                    <button type="submit" class="x-button x-button-primary" onclick="return confirm('Send payslips to all employees?')">
                                        {{ __('Send Payslips') }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('accounting.payroll-runs.print-payslips', $run) }}" class="x-button x-button-ghost" target="_blank">
                                {{ __('Print Payslips') }}
                            </a>
                            <a href="{{ route('accounting.payroll-runs.paye-schedule', $run) }}" class="x-button x-button-ghost" target="_blank">
                                {{ __('PAYE Schedule') }}
                            </a>
                            <a href="{{ route('accounting.payroll-runs.pension-schedule', $run) }}" class="x-button x-button-ghost" target="_blank">
                                {{ __('Pension Schedule') }}
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="kpi-card">
                            <p class="kpi-label">{{ __('Gross Pay') }}</p>
                            <p class="kpi-value">{{ format_money($run->total_gross) }}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">{{ __('Total PAYE') }}</p>
                            <p class="kpi-value text-brick">{{ format_money($run->total_paye) }}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">{{ __('Total Pension') }}</p>
                            <p class="kpi-value text-gold">{{ format_money($run->total_pension) }}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">{{ __('Total Deductions') }}</p>
                            <p class="kpi-value text-gold">{{ format_money($run->total_deductions) }}</p>
                        </div>
                        <div class="kpi-card">
                            <p class="kpi-label">{{ __('Net Pay') }}</p>
                            <p class="kpi-value text-forest">{{ format_money($run->total_net_pay) }}</p>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Employee Items') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Employee Name') }}</th>
                                        <th class="text-right">{{ __('Basic Pay') }}</th>
                                        <th class="text-right">{{ __('Allowances') }}</th>
                                        <th class="text-right">{{ __('Gross') }}</th>
                                        <th class="text-right">{{ __('PAYE') }}</th>
                                        <th class="text-right">{{ __('Pension EE') }}</th>
                                        <th class="text-right">{{ __('Deductions') }}</th>
                                        <th class="text-right">{{ __('Net Pay') }}</th>
                                        <th class="text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($run->items as $item)
                                        <tr>
                                            <td>{{ $item->employee->name ?? '—' }}</td>
                                            <td class="numeric">{{ format_money($item->basic_pay) }}</td>
                                            <td class="numeric">{{ format_money($item->allowances) }}</td>
                                            <td class="numeric">{{ format_money($item->gross_pay) }}</td>
                                            <td class="text-brick text-right">{{ format_money($item->paye) }}</td>
                                            <td class="text-gold text-right">{{ format_money($item->pension_ee) }}</td>
                                            <td class="text-gold text-right">{{ format_money($item->other_deductions) }}</td>
                                            <td class="text-forest text-right font-bold">{{ format_money($item->net_pay) }}</td>
                                            <td class="text-right">
                                                @if(in_array($run->status, ['posted', 'partially_paid', 'fully_paid']) && !$item->is_paid)
                                                    <button type="button" @click="openPaymentModal({{ $item->id }}, '{{ addslashes($item->employee->name ?? '') }}', {{ $item->net_pay }})" class="text-forest hover:text-forest/80">
                                                        {{ __('Pay') }}
                                                    </button>
                                                @endif
                                                <a href="{{ route('accounting.payroll-runs.print-payslips', $run) }}?employee={{ $item->employee_id }}" class="text-ink-soft hover:text-ink" target="_blank">
                                                    {{ __('Payslip') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-ink-soft">
                                                {{ __('No employee items found.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card p-6" x-data="{ showPayeModal: false, showPensionModal: false }">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Remittances') }}</p>
                        <div class="flex items-center space-x-3 mb-4">
                            <button type="button" @click="showPayeModal = true" class="x-button x-button-negative">
                                {{ __('Record PAYE Remittance') }}
                            </button>
                            <button type="button" @click="showPensionModal = true" class="x-button x-button-negative">
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
                                                <label for="paye_amount" class="form-section-label">{{ __('Amount') }}</label>
                                                <input id="paye_amount" name="amount" type="number" step="0.01" min="0" class="input mt-1" :value="old('amount', $run->total_paye)" required />
                                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="paye_date" class="form-section-label">{{ __('Payment Date') }}</label>
                                                <input id="paye_date" name="payment_date" type="date" class="input mt-1" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="paye_bank_account_id" class="form-section-label">{{ __('Bank Account') }}</label>
                                                <select id="paye_bank_account_id" name="bank_account_id" class="input mt-1" required>
                                                    <option value="">{{ __('Select Bank Account') }}</option>
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
                                            <button type="button" @click="showPayeModal = false" class="x-button x-button-ghost">{{ __('Cancel') }}</button>
                                            <button type="submit" class="x-button x-button-primary">{{ __('Record Remittance') }}</button>
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
                                                <label for="pension_ee_amount" class="form-section-label">{{ __('Employee Pension Amount') }}</label>
                                                <input id="pension_ee_amount" name="employee_amount" type="number" step="0.01" min="0" class="input mt-1" :value="old('employee_amount')" required />
                                                <x-input-error :messages="$errors->get('employee_amount')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="pension_er_amount" class="form-section-label">{{ __('Employer Pension Amount') }}</label>
                                                <input id="pension_er_amount" name="employer_amount" type="number" step="0.01" min="0" class="input mt-1" :value="old('employer_amount')" required />
                                                <x-input-error :messages="$errors->get('employer_amount')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="pension_date" class="form-section-label">{{ __('Payment Date') }}</label>
                                                <input id="pension_date" name="payment_date" type="date" class="input mt-1" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="pension_bank_account_id" class="form-section-label">{{ __('Bank Account') }}</label>
                                                <select id="pension_bank_account_id" name="bank_account_id" class="input mt-1" required>
                                                    <option value="">{{ __('Select Bank Account') }}</option>
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
                                            <button type="button" @click="showPensionModal = false" class="x-button x-button-ghost">{{ __('Cancel') }}</button>
                                            <button type="submit" class="x-button x-button-primary">{{ __('Record Remittance') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(isset($payments) && $payments->count())
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Payment History') }}</p>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Employee') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                            <th>{{ __('Payment Date') }}</th>
                                            <th>{{ __('Bank Account') }}</th>
                                            <th class="text-center">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payrollRunItem->employee->name ?? '—' }}</td>
                                                <td class="numeric">{{ format_money($payment->amount) }}</td>
                                                <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                                <td>{{ $payment->bankAccount->name ?? '—' }}</td>
                                                <td class="text-center"><span class="status-pill positive">{{ ucfirst($payment->status ?? 'completed') }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-ink-soft">{{ __('No payments recorded yet.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(isset($deliveries) && $deliveries->count())
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Delivery Status') }}</p>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Employee') }}</th>
                                            <th class="text-center">{{ __('Status') }}</th>
                                            <th>{{ __('Sent At') }}</th>
                                            <th>{{ __('Error') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($deliveries as $delivery)
                                            <tr>
                                                <td>{{ $delivery->payrollRunItem->employee->name ?? '—' }}</td>
                                                <td class="text-center">
                                                    @php
                                                        $deliveryStatusColors = [
                                                            'pending' => 'neutral',
                                                            'sent' => 'info',
                                                            'delivered' => 'positive',
                                                            'failed' => 'negative',
                                                        ];
                                                        $dColor = $deliveryStatusColors[$delivery->status] ?? 'neutral';
                                                    @endphp
                                                    <span class="status-pill {{ $dColor }}">{{ ucfirst($delivery->status) }}</span>
                                                </td>
                                                <td>{{ $delivery->sent_at?->format('M d, Y H:i') ?? '—' }}</td>
                                                <td class="text-sm text-brick">{{ $delivery->error ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-ink-soft">{{ __('No deliveries recorded yet.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.payroll-runs.print', $run), 'icon' => 'print', 'title' => __('Print')],
                        ['route' => route('accounting.payroll-runs.print-payslips', $run), 'icon' => 'payslip', 'title' => __('Payslips')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.payroll-runs.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
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
                                <label for="payment_amount" class="form-section-label">{{ __('Amount') }}</label>
                                <input id="payment_amount" name="amount" type="number" step="0.01" min="0" class="input mt-1" :value="amount" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div>
                                <label for="payment_payment_date" class="form-section-label">{{ __('Payment Date') }}</label>
                                <input id="payment_payment_date" name="payment_date" type="date" class="input mt-1" :value="new Date().toISOString().split('T')[0]" required />
                                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                            </div>
                            <div>
                                <label for="payment_bank_account_id" class="form-section-label">{{ __('Bank Account') }}</label>
                                <select id="payment_bank_account_id" name="bank_account_id" class="input mt-1" required>
                                    <option value="">{{ __('Select Bank Account') }}</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="close()" class="x-button x-button-ghost">{{ __('Cancel') }}</button>
                            <button type="submit" class="x-button x-button-primary">{{ __('Record Payment') }}</button>
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
