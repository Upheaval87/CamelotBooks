<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        @php
            $employee = $payslip->employee;
            $company = App\Models\Company::find(session('current_company_id'));
            $empInitials = strtoupper(substr($employee?->first_name ?? '', 0, 1) . substr($employee?->last_name ?? '', 0, 1));
            $companyInitials = strtoupper(substr($company?->name ?? 'CB', 0, 2));
            $grossPay = $payslip->gross_pay ?? 0;
            $totalDeductions = $payslip->total_deductions ?? 0;
            $netPay = $payslip->net_pay ?? 0;
            $netPct = $grossPay > 0 ? round(($netPay / $grossPay) * 100, 1) : 0;
            $employerCost = $grossPay + ($payslip->pension_er ?? 0);
            $payRef = 'PS-' . str_pad($payslip->id ?? 0, 5, '0', STR_PAD_LEFT);
            $runNumber = $payslip->payrollRun?->run_number ?? 'PR-' . str_pad($payslip->payroll_run_id ?? 0, 4, '0', STR_PAD_LEFT);
        @endphp

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('payroll.dashboard') }}">{{ __('Payroll') }}</a> ›
            <a href="{{ route('payroll.payslips.index') }}">{{ __('Payslips') }}</a> ›
            <span class="here">{{ $employee?->employee_number ?? 'EMP' }} · {{ $payslip->period_label ?? $runNumber }}</span>
        </nav>

        {{-- Toolbar --}}
        <div class="pr-page-head" style="border:none">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <div class="pr-segp">
                    <button type="button" class="on">{{ $payslip->period_label ?? $runNumber }}</button>
                </div>
                @if($payslip->employee_id)
                    <a href="{{ route('payroll.payslips.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('← All Payslips') }}</a>
                @endif
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                @if(($payslip->status ?? '') !== 'sent')
                    <form method="POST" action="{{ route('payroll.payslips.send', $payslip) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Email') }}</button>
                    </form>
                @endif
                <button type="button" onclick="window.print()" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Print') }}</button>
            </div>
        </div>

        {{-- Paper document --}}
        <div class="pr-paper" style="margin-top:8px">

            {{-- Watermark --}}
            @if(($payslip->status ?? '') === 'paid')
                <div class="pr-wm">PAID</div>
            @endif

            {{-- Company letterhead --}}
            <div class="pr-pp-head">
                <div style="display:flex;gap:12px;align-items:center">
                    <span class="pr-pp-logo">{{ $companyInitials }}</span>
                    <div>
                        <div style="font-size:16px;font-weight:800;color:var(--ink)">{{ $company?->name ?? 'Company' }}</div>
                        <div style="font-size:10.5px;color:var(--muted);margin-top:2px">
                            {{ $company?->address ?? '' }}{{ $company?->tax_id ? ' · PAYE PIN ' . $company->tax_id : '' }}
                        </div>
                    </div>
                </div>
                <div class="pr-pp-title">
                    <div class="big">PAYSLIP</div>
                    <div class="ref">{{ $payRef }}</div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:8px">
                        @if(($payslip->status ?? '') === 'paid')
                            <span class="pr-tag paid">● {{ __('Paid') }} · {{ $payslip->pay_date?->format('d M Y') ?? $payslip->payrollRun?->pay_date?->format('d M Y') ?? '—' }}</span>
                        @elseif(($payslip->status ?? '') === 'sent')
                            <span class="pr-tag paid">● {{ __('Sent') }}</span>
                        @else
                            <span class="pr-tag" style="background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)">● {{ __('Pending') }}</span>
                        @endif
                        <span class="pr-tag conf">{{ __('Confidential') }}</span>
                    </div>
                </div>
            </div>

            {{-- Employee strip --}}
            <div class="pr-pp-emp">
                <span class="pr-pp-ava">{{ $empInitials }}</span>
                <div>
                    <div style="font-size:16px;font-weight:800;color:var(--ink)">{{ $employee?->full_name ?? '—' }}</div>
                    <div style="font-size:11.5px;color:var(--muted);margin-top:2px">
                        {{ $employee?->position ?? '—' }} · {{ $employee?->department ?? '—' }} · {{ $employee?->employee_number ?? '—' }} · {{ ucfirst($employee?->employment_type ?? 'full-time') }}
                    </div>
                </div>
                <div class="pr-pp-facts">
                    <div class="pr-pf">
                        <div class="l">{{ __('Period') }}</div>
                        <div class="v">{{ $payslip->period_label ?? ($payslip->payrollRun?->period_start?->format('d M') . ' – ' . $payslip->payrollRun?->period_end?->format('d M Y') ?? '—') }}</div>
                    </div>
                    <div class="pr-pf">
                        <div class="l">{{ __('Method') }}</div>
                        <div class="v">{{ $employee?->bank_name ?? '—' }} {{ $employee?->bank_account_number ? '····' . substr($employee->bank_account_number, -4) : '' }}</div>
                    </div>
                    <div class="pr-pf">
                        <div class="l">{{ __('PAYE PIN') }}</div>
                        <div class="v">{{ $employee?->tax_number ?? '—' }}</div>
                    </div>
                    <div class="pr-pf">
                        <div class="l">{{ __('Pension №') }}</div>
                        <div class="v">{{ $employee?->pension_number ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Earnings / Deductions two columns --}}
            <div class="pr-pp-cols">

                {{-- Earnings --}}
                <div class="pr-pp-col">
                    <h4>{{ __('Earnings') }}</h4>
                    <table class="pr-pt">
                        <thead>
                            <tr>
                                <th>{{ __('Item') }}</th>
                                <th>{{ __('Basis') }}</th>
                                <th class="num">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="b">{{ __('Basic Pay') }}</td>
                                <td>{{ __('Monthly') }}</td>
                                <td class="num">{{ format_number($payslip->basic_pay ?? 0) }}</td>
                            </tr>
                            @if(($payslip->housing_allowance ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Housing Allowance') }}</td>
                                    <td>{{ __('10%') }}</td>
                                    <td class="num">{{ format_number($payslip->housing_allowance) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->transport_allowance ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Transport Allowance') }}</td>
                                    <td>{{ __('Fixed') }}</td>
                                    <td class="num">{{ format_number($payslip->transport_allowance) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->overtime ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Overtime') }}</td>
                                    <td>{{ __('1.5×') }}</td>
                                    <td class="num">{{ format_number($payslip->overtime) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->bonus ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Bonus') }}</td>
                                    <td>{{ __('Variable') }}</td>
                                    <td class="num">{{ format_number($payslip->bonus) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->commission ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Commission') }}</td>
                                    <td>{{ __('Variable') }}</td>
                                    <td class="num">{{ format_number($payslip->commission) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->total_allowances ?? 0) > 0 && ($payslip->housing_allowance ?? 0) + ($payslip->transport_allowance ?? 0) == 0)
                                <tr>
                                    <td class="b">{{ __('Other Allowances') }}</td>
                                    <td>{{ __('Fixed') }}</td>
                                    <td class="num">{{ format_number($payslip->total_allowances) }}</td>
                                </tr>
                            @endif
                            <tr class="tot">
                                <td>{{ __('Gross Pay') }}</td>
                                <td></td>
                                <td class="num">{{ format_number($grossPay) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Deductions --}}
                <div class="pr-pp-col">
                    <h4>{{ __('Deductions') }}</h4>
                    <table class="pr-pt">
                        <thead>
                            <tr>
                                <th>{{ __('Item') }}</th>
                                <th>{{ __('Basis') }}</th>
                                <th class="num">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(($payslip->paye ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('PAYE') }}</td>
                                    <td>{{ __('Tax table') }}</td>
                                    <td class="num">{{ format_number($payslip->paye) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->pension_ee ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Pension (EE)') }}</td>
                                    <td>{{ __('Employee %') }}</td>
                                    <td class="num">{{ format_number($payslip->pension_ee) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->loan_deduction ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Loan Repayment') }}</td>
                                    <td>{{ __('Instalment') }}</td>
                                    <td class="num">{{ format_number($payslip->loan_deduction) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->other_deductions ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('Other Deductions') }}</td>
                                    <td>{{ __('Fixed') }}</td>
                                    <td class="num">{{ format_number($payslip->other_deductions) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->nhif ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('NHIF') }}</td>
                                    <td>{{ __('Statutory') }}</td>
                                    <td class="num">{{ format_number($payslip->nhif) }}</td>
                                </tr>
                            @endif
                            @if(($payslip->nssf ?? 0) > 0)
                                <tr>
                                    <td class="b">{{ __('NSSF') }}</td>
                                    <td>{{ __('Statutory') }}</td>
                                    <td class="num">{{ format_number($payslip->nssf) }}</td>
                                </tr>
                            @endif
                            @if($totalDeductions == 0)
                                <tr>
                                    <td colspan="3" style="text-align:center;color:var(--muted);padding:20px 0">{{ __('No deductions') }}</td>
                                </tr>
                            @endif
                            <tr class="tot">
                                <td>{{ __('Total Deductions') }}</td>
                                <td></td>
                                <td class="num">{{ format_number($totalDeductions) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Net Pay card --}}
            <div class="pr-net-card">
                <div class="pr-nc-net">
                    <div class="l">{{ __('Net Pay') }}</div>
                    <div class="v">{{ $company?->base_currency ?? '' }} {{ format_number($netPay) }}</div>
                    <div class="s">{{ $netPct }}% {{ __('of gross reaches the employee') }} · {{ __('paid') }} {{ $payslip->pay_date?->format('d M Y') ?? $payslip->payrollRun?->pay_date?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="pr-nc-stats">
                    <div class="pr-nc">
                        <div class="l">{{ __('Gross') }}</div>
                        <div class="v">{{ format_number($grossPay) }}</div>
                    </div>
                    <div class="pr-nc">
                        <div class="l">{{ __('Deductions') }}</div>
                        <div class="v soft">{{ format_number($totalDeductions) }}</div>
                    </div>
                    <div class="pr-nc">
                        <div class="l">{{ __('Employer cost') }}</div>
                        <div class="v">{{ format_number($employerCost) }}</div>
                    </div>
                </div>
            </div>

            {{-- Info cards: employer contributions, YTD, leave --}}
            <div class="pr-pp-cards">

                {{-- Employer contributions --}}
                <div class="pr-pcard">
                    <div class="t">{{ __('Employer contributions') }}</div>
                    @if(($payslip->pension_er ?? 0) > 0)
                        <div class="r">
                            <span>{{ __('Pension (ER)') }}</span>
                            <b>{{ format_number($payslip->pension_er) }}</b>
                        </div>
                    @endif
                    @if(($payslip->nhif_employer ?? 0) > 0)
                        <div class="r">
                            <span>{{ __('NHIF (Employer)') }}</span>
                            <b>{{ format_number($payslip->nhif_employer) }}</b>
                        </div>
                    @endif
                    <div class="r" style="border-top:1px dashed var(--line);margin-top:4px;padding-top:6px">
                        <span>{{ __('Total employer cost') }}</span>
                        <b>{{ format_number($employerCost) }}</b>
                    </div>
                </div>

                {{-- Year to date --}}
                <div class="pr-pcard">
                    <div class="t">{{ __('Year to date') }}</div>
                    <div class="r">
                        <span>{{ __('YTD Gross') }}</span>
                        <b>{{ format_number($ytdGross ?? $grossPay) }}</b>
                    </div>
                    <div class="r">
                        <span>{{ __('YTD PAYE') }}</span>
                        <b>{{ format_number($ytdPaye ?? $payslip->paye ?? 0) }}</b>
                    </div>
                    <div class="r">
                        <span>{{ __('YTD Pension') }}</span>
                        <b>{{ format_number($ytdPension ?? $payslip->pension_ee ?? 0) }}</b>
                    </div>
                </div>

                {{-- Leave & attendance --}}
                <div class="pr-pcard">
                    <div class="t">{{ __('Leave & attendance') }}</div>
                    <div class="r">
                        <span>{{ __('Leave balance') }}</span>
                        <b>{{ $leaveBalance ?? '—' }} {{ __('days') }}</b>
                    </div>
                    <div class="r">
                        <span>{{ __('Overtime') }}</span>
                        <b>{{ $overtimeHours ?? '—' }}h</b>
                    </div>
                    <div class="r">
                        <span>{{ __('Absences') }}</span>
                        <b>{{ $absences ?? 0 }}</b>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="pr-pp-foot">
                <span class="pr-qr"></span>
                <span>
                    {{ __('Paid to') }} {{ $employee?->bank_name ?? '—' }}
                    {{ $employee?->bank_account_number ? '····' . substr($employee->bank_account_number, -4) : '' }}
                    on {{ $payslip->pay_date?->format('d M Y') ?? $payslip->payrollRun?->pay_date?->format('d M Y') ?? '—' }}
                    · {{ __('Ref') }} {{ $payRef }}
                    · {{ __('System-generated, no signature required.') }}
                </span>
                <span style="margin-left:auto">
                    {{ __('Questions?') }} <a href="#">{{ __('Contact HR →') }}</a>
                </span>
            </div>
        </div>
    </div>
</x-app-layout>
