<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6" x-data="{ activeTab: 'overview' }">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs mb-4">
            <a href="{{ route('accounting.payroll.dashboard') }}">{{ __('Payroll') }}</a>
            <span>›</span>
            <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Employees') }}</a>
            <span>›</span>
            <span class="here">{{ $employee->employee_number ?: $employee->full_name }}</span>
        </nav>

        {{-- Sticky page head --}}
        <div class="pr-page-head mb-6" style="position:sticky;top:var(--topbar-h,106px);z-index:20;background:rgba(238,244,244,.9);backdrop-filter:blur(12px)">
            <div>
                <h1>{{ $employee->full_name }}</h1>
                <div class="sub">{{ $employee->employee_number }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('accounting.payroll.employees.edit', $employee) }}" class="pr-btn pr-btn-ghost pr-btn-sm">✎ {{ __('Edit') }}</a>
                <a href="{{ route('accounting.payroll.payslips.show', $employee->deliveries->first() ?? 0) }}" class="pr-btn pr-btn-ghost pr-btn-sm">📄 {{ __('Print Payslip') }}</a>
                <a href="{{ route('accounting.payroll.employees.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">← {{ __('Back to Employees') }}</a>
            </div>
        </div>

        {{-- Profile header --}}
        <div class="pr-card" style="margin-bottom:20px">
            <div class="pr-prof">
                @php
                    $initials = strtoupper(
                        substr($employee->first_name, 0, 1) .
                        substr($employee->last_name, 0, 1)
                    );
                @endphp
                <span class="pr-ava-xl">{{ $initials }}</span>
                <div>
                    <div class="pr-prof-n">
                        {{ $employee->full_name }}
                        @if($employee->employee_number)
                            <span class="pr-mono-chip">{{ $employee->employee_number }}</span>
                        @endif
<x-payroll::badge :status="$employee->employment_status ?? ($employee->is_active ? 'active' : 'terminated')" type="employee" />
                    </div>
                    <div class="pr-prof-c">
                        @if($employee->position || $employee->department)
                            <span>{{ $employee->position }}{{ ($employee->position && $employee->department) ? ' · ' : '' }}{{ $employee->department }}</span>
                        @endif
                        @if($employee->employment_status)
                            <span>{{ __(ucwords(str_replace('_', ' ', $employee->employment_type ?? $employee->employment_status))) }}</span>
                        @endif
                        @if($employee->hire_date)
                            <span>{{ __('Joined') }} {{ $employee->hire_date->format('M d, Y') }}</span>
                        @endif
                        @if($employee->bank_name && $employee->bank_account_number)
                            <span>{{ $employee->bank_name }} ····{{ substr($employee->bank_account_number, -4) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Salary summary bar --}}
        @php
            $structure = $employee->currentSalaryStructure;
            $basicPay = $structure->basic_pay ?? 0;
            $totalAllowances = $structure ? $structure->items->sum('amount') : 0;
            $grossPay = $basicPay + $totalAllowances;
        @endphp
        <div class="pr-sumbar" style="margin-bottom:20px">
            <div class="pr-sumbar-cell">
                <div class="l">{{ __('Basic Pay') }}</div>
                <div class="v">{{ format_number($basicPay) }}</div>
                <div class="n">{{ __('per month') }}</div>
            </div>
            <div class="pr-sumbar-cell">
                <div class="l">{{ __('Total Allowances') }}</div>
                <div class="v">{{ format_number($totalAllowances) }}</div>
                <div class="n">{{ $structure->items->count() }} {{ __('items') }}</div>
            </div>
            <div class="pr-sumbar-cell">
                <div class="l">{{ __('Net Pay (YTD)') }}</div>
                <div class="v">{{ format_number($ytdNetPay) }}</div>
                <div class="n">{{ __('after PAYE + pension') }}</div>
            </div>
            <div class="pr-sumbar-cell pr-sumbar-hero">
                <div class="l">{{ __('YTD Gross') }}</div>
                <div class="v">{{ format_number($ytdGross) }}</div>
            </div>
        </div>

        {{-- Tabs card --}}
        <div class="pr-card">

            {{-- Tab bar --}}
            <div class="pr-tabs" role="tablist">
                <button class="pr-tab" :class="activeTab === 'overview' ? 'on' : ''" @click="activeTab = 'overview'" role="tab">{{ __('Overview') }}</button>
                <button class="pr-tab" :class="activeTab === 'salary' ? 'on' : ''" @click="activeTab = 'salary'" role="tab">{{ __('Salary Structure') }}</button>
                <button class="pr-tab" :class="activeTab === 'tax' ? 'on' : ''" @click="activeTab = 'tax'" role="tab">{{ __('Tax (PAYE)') }}</button>
                <button class="pr-tab" :class="activeTab === 'pension' ? 'on' : ''" @click="activeTab = 'pension'" role="tab">{{ __('Pension') }}</button>
                <button class="pr-tab" :class="activeTab === 'allowances' ? 'on' : ''" @click="activeTab = 'allowances'" role="tab">{{ __('Allowances') }}</button>
                <button class="pr-tab" :class="activeTab === 'payments' ? 'on' : ''" @click="activeTab = 'payments'" role="tab">{{ __('Payments') }}</button>
                <button class="pr-tab" :class="activeTab === 'deductions' ? 'on' : ''" @click="activeTab = 'deductions'" role="tab">{{ __('Deductions') }}</button>
                <button class="pr-tab" :class="activeTab === 'loans' ? 'on' : ''" @click="activeTab = 'loans'" role="tab">{{ __('Loans') }}</button>
                <button class="pr-tab" :class="activeTab === 'payslips' ? 'on' : ''" @click="activeTab = 'payslips'" role="tab">{{ __('Payslips') }}</button>
                <button class="pr-tab" :class="activeTab === 'documents' ? 'on' : ''" @click="activeTab = 'documents'" role="tab">{{ __('Documents') }}</button>
                <button class="pr-tab" :class="activeTab === 'audit' ? 'on' : ''" @click="activeTab = 'audit'" role="tab">{{ __('Audit Trail') }}</button>
                <button class="pr-tab" :class="activeTab === 'settings' ? 'on' : ''" @click="activeTab = 'settings'" role="tab">{{ __('Settings') }}</button>
            </div>

            {{-- ═══════════════ Tab 1: Overview ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'overview' ? 'on' : ''">
                <div class="pr-pad">
                    <div class="pr-g3">
                        <div class="pr-fld">
                            <div class="l">{{ __('Full Name') }}</div>
                            <div class="v">{{ $employee->full_name }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Gender / DOB') }}</div>
                            <div class="v">
                                {{ $employee->gender ? __(ucwords($employee->gender)) : '—' }}
                                @if($employee->date_of_birth) · {{ $employee->date_of_birth->format('M d, Y') }} @endif
                            </div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('National ID') }}</div>
                            <div class="v">{{ $employee->national_id ?: '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Phone / Email') }}</div>
                            <div class="v">{{ $employee->phone ?: '—' }}{{ ($employee->phone && $employee->email) ? ' · ' : '' }}{{ $employee->email ?: '' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Address') }}</div>
                            <div class="v">{{ $employee->address ?: '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Emergency Contact') }}</div>
                            <div class="v">—</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Dept / Position') }}</div>
                            <div class="v">{{ $employee->department ?: '—' }}{{ ($employee->department && $employee->position) ? ' · ' : '' }}{{ $employee->position ?: '' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Branch') }}</div>
                            <div class="v">{{ $employee->branch?->name ?? '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Employment Type') }}</div>
                            <div class="v">{{ $employee->employment_type ? __(ucwords(str_replace('_', ' ', $employee->employment_type))) : '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Hire Date') }}</div>
                            <div class="v">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Status') }}</div>
                            <div class="v"><x-payroll::badge :status="$employee->employment_status ?? ($employee->is_active ? 'active' : 'terminated')" type="employee" /></div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Cost Centre') }}</div>
                            <div class="v">{{ $employee->costCenter?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 2: Salary Structure ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'salary' ? 'on' : ''">
                <div class="pr-pad">
                    @if($structure)
                        <div class="pr-g3" style="margin-bottom:20px">
                            <div class="pr-fld">
                                <div class="l">{{ __('Basic Pay') }}</div>
                                <div class="v">{{ format_number($structure->basic_pay) }}</div>
                            </div>
                            <div class="pr-fld">
                                <div class="l">{{ __('Effective From') }}</div>
                                <div class="v">{{ $structure->effective_from ? $structure->effective_from->format('M d, Y') : '—' }}</div>
                            </div>
                            <div class="pr-fld">
                                <div class="l">{{ __('Effective To') }}</div>
                                <div class="v">{{ $structure->effective_to ? $structure->effective_to->format('M d, Y') : __('Current') }}</div>
                            </div>
                        </div>

                        <div class="pr-li-wrap">
                            <table style="min-width:0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Item') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Basis') }}</th>
                                        <th class="num">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ __('Basic Pay') }}</td>
                                        <td><span class="pr-tchip pr-tchip-green">{{ __('Earning') }}</span></td>
                                        <td class="pr-em">{{ __('Monthly') }}</td>
                                        <td class="pr-numr bold">{{ format_number($structure->basic_pay) }}</td>
                                    </tr>
                                    @foreach($structure->items as $item)
                                        <tr>
                                            <td style="font-weight:700;color:var(--ink)">{{ $item->allowance?->name ?? $item->name ?? '—' }}</td>
                                            <td>
                                                @if(($item->type ?? 'allowance') === 'deduction')
                                                    <span class="pr-tchip" style="color:var(--red-2)">{{ __('Deduction') }}</span>
                                                @else
                                                    <span class="pr-tchip pr-tchip-green">{{ __('Allowance') }}</span>
                                                @endif
                                            </td>
                                            <td class="pr-em">{{ __('Monthly') }}</td>
                                            <td class="pr-numr">{{ format_number($item->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3">{{ __('Total Gross Pay') }}</td>
                                        <td class="pr-numr bold">{{ format_number($grossPay) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="pr-pad" style="text-align:center;padding:40px 24px;color:var(--muted)">
                            {{ __('No salary structure configured for this employee.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════ Tab 3: Tax (PAYE) ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'tax' ? 'on' : ''">
                <div class="pr-pad">
                    <div class="pr-g3" style="margin-bottom:20px">
                        <div class="pr-fld">
                            <div class="l">{{ __('Tax ID') }}</div>
                            <div class="v">{{ $employee->tax_id ?: '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('PAYE Table') }}</div>
                            <div class="v">{{ $employee->pensionScheme?->name ?? __('Default') }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Taxable Income (Monthly)') }}</div>
                            <div class="v">{{ format_number($grossPay) }}</div>
                        </div>
                    </div>

                    <div class="pr-card" style="border:1px solid var(--border);border-radius:14px;overflow:hidden">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--line)">
                            <h2 style="font-size:14px;font-weight:800;color:var(--ink)">{{ __('PAYE Calculation') }}</h2>
                        </div>
                        <div class="pr-li-wrap">
                            <table style="min-width:0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Description') }}</th>
                                        <th class="num">{{ __('Monthly') }}</th>
                                        <th class="num">{{ __('Annual') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ __('Gross Pay') }}</td>
                                        <td class="pr-numr">{{ format_number($grossPay) }}</td>
                                        <td class="pr-numr">{{ format_number($grossPay * 12) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Tax-free Allowances') }}</td>
                                        <td class="pr-numr pr-em">—</td>
                                        <td class="pr-numr pr-em">—</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ __('Taxable Income') }}</td>
                                        <td class="pr-numr bold">{{ format_number($grossPay) }}</td>
                                        <td class="pr-numr bold">{{ format_number($grossPay * 12) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:700;color:var(--red-2)">{{ __('PAYE Amount') }}</td>
                                        <td class="pr-numr red">—</td>
                                        <td class="pr-numr red">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 4: Pension ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'pension' ? 'on' : ''">
                <div class="pr-pad">
                    @php
                        $pensionScheme = $employee->pensionScheme;
                    @endphp
                    <div class="pr-g3">
                        <div class="pr-fld">
                            <div class="l">{{ __('Scheme Name') }}</div>
                            <div class="v">{{ $pensionScheme?->name ?? '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Pension Member №') }}</div>
                            <div class="v">{{ $employee->pension_member_number ?: '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Employee Rate') }}</div>
                            <div class="v">{{ $pensionScheme?->employee_rate ? $pensionScheme->employee_rate . '%' : '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Employer Rate') }}</div>
                            <div class="v">{{ $pensionScheme?->employer_rate ? $pensionScheme->employer_rate . '%' : '—' }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Employee Contribution') }}</div>
                            <div class="v">{{ format_number($grossPay * ($pensionScheme?->employee_rate ?? 0) / 100) }}</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Employer Contribution') }}</div>
                            <div class="v">{{ format_number($grossPay * ($pensionScheme?->employer_rate ?? 0) / 100) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 5: Allowances ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'allowances' ? 'on' : ''">
                <div class="pr-pad">
                    @if($structure && $structure->items->count())
                        <div class="pr-li-wrap">
                            <table style="min-width:0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Allowance') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th class="num">{{ __('Monthly Amount') }}</th>
                                        <th class="num">{{ __('Annual Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($structure->items as $item)
                                        <tr>
                                            <td style="font-weight:700;color:var(--ink)">{{ $item->allowance?->name ?? $item->name ?? '—' }}</td>
                                            <td>
                                                @if(($item->type ?? 'allowance') === 'deduction')
                                                    <span class="pr-tchip" style="color:var(--red-2)">{{ __('Deduction') }}</span>
                                                @else
                                                    <span class="pr-tchip pr-tchip-green">{{ __('Allowance') }}</span>
                                                @endif
                                            </td>
                                            <td class="pr-numr">{{ format_number($item->amount) }}</td>
                                            <td class="pr-numr">{{ format_number($item->amount * 12) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">{{ __('Total Allowances') }}</td>
                                        <td class="pr-numr bold">{{ format_number($totalAllowances) }}</td>
                                        <td class="pr-numr bold">{{ format_number($totalAllowances * 12) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div style="text-align:center;padding:40px 24px;color:var(--muted)">
                            {{ __('No allowances configured.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════ Tab 6: Payments ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'payments' ? 'on' : ''">
                <div class="pr-pad">
                    @if($recentPayments->count())
                        <div class="pr-li-wrap">
                            <table style="min-width:0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Payment №') }}</th>
                                        <th>{{ __('Run') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th class="num">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPayments as $payment)
                                        <tr>
                                            <td class="pr-em">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}</td>
                                            <td class="pr-mono">{{ $payment->payment_number ?: '—' }}</td>
                                            <td class="pr-mono">{{ $payment->payrollRun?->run_number ?? '—' }}</td>
                                            <td class="pr-em">{{ $payment->bankAccount?->name ?? __('Bank Transfer') }}</td>
                                            <td class="pr-numr bold">{{ format_number($payment->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="text-align:center;padding:40px 24px;color:var(--muted)">
                            {{ __('No payment records found.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════ Tab 7: Deductions ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'deductions' ? 'on' : ''">
                <div class="pr-pad">
                    @php
                        $deductionItems = $structure ? $structure->items->filter(fn($item) => ($item->type ?? 'allowance') === 'deduction') : collect();
                        $loanDeductions = $activeLoans->sum('monthly_deduction');
                    @endphp

                    <div class="pr-li-wrap" style="margin-bottom:20px">
                        <table style="min-width:0">
                            <thead>
                                <tr>
                                    <th>{{ __('Deduction') }}</th>
                                    <th>{{ __('Basis') }}</th>
                                    <th class="num">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deductionItems as $item)
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ $item->allowance?->name ?? $item->name ?? '—' }}</td>
                                        <td class="pr-em">{{ __('Monthly') }}</td>
                                        <td class="pr-numr red">{{ format_number($item->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align:center;color:var(--muted)">{{ __('No statutory or voluntary deductions configured.') }}</td>
                                    </tr>
                                @endforelse
                                @if($loanDeductions > 0)
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ __('Loan Deductions') }}</td>
                                        <td class="pr-em">{{ __('Monthly') }}</td>
                                        <td class="pr-numr red">{{ format_number($loanDeductions) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            @if($deductionItems->count() || $loanDeductions > 0)
                                <tfoot>
                                    <tr>
                                        <td colspan="2">{{ __('Total Deductions') }}</td>
                                        <td class="pr-numr bold red">{{ format_number($deductionItems->sum('amount') + $loanDeductions) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 8: Loans ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'loans' ? 'on' : ''">
                <div class="pr-pad">

                    {{-- Active loans table --}}
                    <div class="pr-li-wrap" style="margin-bottom:24px">
                        <table style="min-width:0">
                            <thead>
                                <tr>
                                    <th>{{ __('Loan №') }}</th>
                                    <th class="num">{{ __('Principal') }}</th>
                                    <th class="num">{{ __('Monthly Deduction') }}</th>
                                    <th class="num">{{ __('Outstanding') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->loans as $loan)
                                    <tr>
                                        <td class="pr-mono">{{ $loan->loan_number ?: '—' }}</td>
                                        <td class="pr-numr">{{ format_number($loan->principal_amount) }}</td>
                                        <td class="pr-numr">{{ format_number($loan->monthly_deduction) }}</td>
                                        <td class="pr-numr bold">{{ format_number($loan->outstanding_balance) }}</td>
                                        <td>
                                            @if($loan->status === 'active')
                                                <x-payroll::badge status="active" type="employee" label="{{ __('Active') }}" />
                                            @elseif($loan->status === 'paid_off')
                                                <span class="pr-tchip pr-tchip-green">{{ __('Paid Off') }}</span>
                                            @else
                                                <span class="pr-tchip">{{ __(ucwords(str_replace('_', ' ', $loan->status))) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--muted)">{{ __('No loans on record.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Create Loan form --}}
                    <div class="pr-card" style="border-radius:16px;overflow:visible">
                        <div class="pr-card-h">
                            <h2>{{ __('Create New Loan') }}</h2>
                        </div>
                        <div class="pr-pad">
                            <form method="POST" action="{{ route('accounting.payroll.people.store') }}" id="loan-create-form">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <input type="hidden" name="type" value="loan">
                                <div class="pr-g3">
                                    <div class="pr-fld">
                                        <label class="l">{{ __('Loan Number') }} <span style="color:var(--red)">*</span></label>
                                        <input class="pr-input" type="text" name="loan_number" required placeholder="{{ __('LN-0001') }}">
                                    </div>
                                    <div class="pr-fld">
                                        <label class="l">{{ __('Principal Amount') }} <span style="color:var(--red)">*</span></label>
                                        <input class="pr-input" type="number" name="principal_amount" step="0.01" min="0" required placeholder="0.00">
                                    </div>
                                    <div class="pr-fld">
                                        <label class="l">{{ __('Monthly Deduction') }} <span style="color:var(--red)">*</span></label>
                                        <input class="pr-input" type="number" name="monthly_deduction" step="0.01" min="0" required placeholder="0.00">
                                    </div>
                                    <div class="pr-fld">
                                        <label class="l">{{ __('Interest Rate (%)') }}</label>
                                        <input class="pr-input" type="number" name="interest_rate" step="0.01" min="0" max="100" placeholder="0.00">
                                    </div>
                                    <div class="pr-fld">
                                        <label class="l">{{ __('Start Date') }} <span style="color:var(--red)">*</span></label>
                                        <input class="pr-input" type="date" name="start_date" required>
                                    </div>
                                    <div class="pr-fld">
                                        <label class="l">{{ __('End Date') }}</label>
                                        <input class="pr-input" type="date" name="end_date">
                                    </div>
                                    <div class="pr-fld" style="grid-column:1/-1">
                                        <label class="l">{{ __('Notes') }}</label>
                                        <textarea class="pr-input" name="notes" rows="3" style="height:auto;padding:12px;border-radius:14px" placeholder="{{ __('Optional notes about this loan...') }}"></textarea>
                                    </div>
                                </div>
                                <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end">
                                    <button type="submit" class="pr-btn pr-btn-sec pr-btn-sm">{{ __('Create Loan') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 9: Payslips ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'payslips' ? 'on' : ''">
                <div class="pr-pad">
                    @if($employee->deliveries->count())
                        <div class="pr-li-wrap">
                            <table style="min-width:0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Run') }}</th>
                                        <th>{{ __('Period') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="num">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employee->deliveries as $delivery)
                                        @php
                                            $run = $delivery->payrollRun;
                                        @endphp
                                        <tr>
                                            <td class="pr-mono">{{ $run?->run_number ?? '—' }}</td>
                                            <td class="pr-em">
                                                @if($run?->pay_period_start && $run?->pay_period_end)
                                                    {{ $run->pay_period_start->format('M d') }} – {{ $run->pay_period_end->format('M d, Y') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($delivery->sent_at)
                                                    <span class="pr-tchip pr-tchip-green">{{ __('Sent') }}</span>
                                                @else
                                                    <span class="pr-tchip">{{ __('Generated') }}</span>
                                                @endif
                                            </td>
                                            <td class="pr-numr">
                                                <a href="{{ route('accounting.payroll.payslips.show', $delivery) }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('View') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="text-align:center;padding:40px 24px;color:var(--muted)">
                            {{ __('No payslips generated yet.') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════ Tab 10: Documents ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'documents' ? 'on' : ''">
                <div class="pr-pad">
                    <div class="pr-attchips">
                        <span class="pr-att">📎 {{ __('NRC Copy') }} <span class="pr-em">({{ __('placeholder') }})</span></span>
                        <span class="pr-att">📎 {{ __('Employment Contract') }} <span class="pr-em">({{ __('placeholder') }})</span></span>
                        <span class="pr-att">📎 {{ __('Passport Photo') }} <span class="pr-em">({{ __('placeholder') }})</span></span>
                    </div>
                    <div style="margin-top:16px;text-align:center;padding:40px 24px;color:var(--muted);border:2px dashed var(--border);border-radius:16px">
                        <div style="font-size:13px;color:var(--muted)">{{ __('Drag & drop files here or click to browse.') }}</div>
                        <div style="font-size:11px;color:var(--faint);margin-top:4px">{{ __('PDF, JPG, PNG, DOC up to 10 MB') }}</div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 11: Audit Trail ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'audit' ? 'on' : ''">
                <div class="pr-pad">
                    <div style="text-align:center;padding:40px 24px;color:var(--muted)">
                        {{ __('Audit trail will display field-level changes, salary adjustments, and status transitions.') }}
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Tab 12: Settings ═══════════════ --}}
            <div class="pr-pane" :class="activeTab === 'settings' ? 'on' : ''">
                <div class="pr-pad">
                    <div class="pr-g3">
                        <div class="pr-fld">
                            <div class="l">{{ __('Emergency Contact Name') }}</div>
                            <div class="v">—</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Emergency Contact Phone') }}</div>
                            <div class="v">—</div>
                        </div>
                        <div class="pr-fld">
                            <div class="l">{{ __('Payslip Password') }}</div>
                            <div class="v">{{ $employee->payslip_password ? __('••••••••') : __('Not set') }}</div>
                        </div>
                        <div class="pr-fld" style="grid-column:1/-1">
                            <div class="l">{{ __('Notes') }}</div>
                            <div class="v">{{ __('No notes added.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
