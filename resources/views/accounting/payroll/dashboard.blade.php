<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <span class="here">{{ __('Payroll Dashboard') }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ __('Payroll Centre') }}</h1>
                <div class="sub">{{ __('Employees, runs, statutory deductions, benefits, payslips and reporting.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('payroll.settings.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">&#9881; {{ __('Settings') }}</a>
                <a href="{{ route('payroll.reports.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">&#128202; {{ __('Reports') }}</a>
                <a href="{{ route('payroll.payslips.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">&#128196; {{ __('Payslips') }}</a>
                <a href="{{ route('payroll.runs.create') }}" class="pr-btn pr-btn-sec pr-btn-sm">&#9654; {{ __('Run Payroll') }}</a>
                <a href="{{ route('payroll.employees.create') }}" class="pr-btn pr-btn-cta pr-btn-sm">+ {{ __('Add Employee') }}</a>
            </div>
        </div>

        {{-- KPI Row 1: Financials --}}
        @php
            $grossPay = $lastRun?->items->sum('gross_pay') ?? 0;
            $netPay = $lastRun?->items->sum('net_pay') ?? 0;
            $payePayable = $lastRun?->items->sum('paye') ?? 0;
            $pensionContrib = $lastRun?->items->sum('pension') ?? 0;
        @endphp
        <div class="pr-kpis" style="margin-bottom:12px">
            <div class="pr-kpi pr-kpi-hero">
                <div class="l">{{ __('Total Gross Pay') }}</div>
                <div class="v">{{ format_number($grossPay) }}</div>
                @if($lastRun)
                    <div class="n" style="color:#dff7f6">{{ $lastRun->pay_period_start?->format('M') }} {{ __('run') }} &middot; {{ $lastRun->items->count() }} {{ __('employees') }}</div>
                @else
                    <div class="n" style="color:#dff7f6">{{ __('No runs yet') }}</div>
                @endif
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Total Net Pay') }}</div>
                <div class="v">{{ format_number($netPay) }}</div>
                <div class="n">{{ __('after PAYE, pension, loans') }}</div>
            </div>
            <div class="pr-kpi pr-kpi-warn">
                <div class="l">{{ __('PAYE Payable') }}</div>
                <div class="v">{{ format_number($payePayable) }}</div>
                <div class="n">{{ __('due next remittance') }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Pension') }}</div>
                <div class="v">{{ format_number($pensionContrib) }}</div>
                <div class="n">{{ __('+ employer contributions') }}</div>
            </div>
        </div>

        {{-- KPI Row 2: People --}}
        <div class="pr-kpis" style="margin-bottom:16px">
            <div class="pr-kpi">
                <div class="l">{{ __('Employees') }}</div>
                <div class="v">{{ $totalEmployees }}</div>
                <div class="n">{{ $activeEmployees }} {{ __('active') }} &middot; {{ $onLeaveEmployees }} {{ __('on leave') }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Active') }}</div>
                <div class="v">{{ $activeEmployees }}</div>
                <div class="n">{{ __('in workforce today') }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('On Leave') }}</div>
                <div class="v">{{ $onLeaveEmployees }}</div>
                <div class="n">{{ __('currently away') }}</div>
            </div>
            <div class="pr-kpi pr-kpi-warn">
                <div class="l">{{ __('Pending Approval') }}</div>
                <div class="v">{{ $pendingApprovals }}</div>
                <div class="n">
                    @if($pendingApprovals > 0)
                        <a href="{{ route('payroll.runs.index') }}" style="color:var(--amber-2)">{{ __('review runs') }} &rarr;</a>
                    @else
                        {{ __('all caught up') }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Nav --}}
        <div class="pr-statgrid" style="margin-bottom:16px">
            <a href="{{ route('payroll.employees.index') }}" class="pr-fbox on">
                <span class="pr-fbox-t pr-t-ink">&#128101;</span>
                <span><span class="l">{{ __('Employees') }}</span><span class="v" style="display:block">{{ $totalEmployees }}</span></span>
            </a>
            <a href="{{ route('payroll.runs.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-mint">&#128197;</span>
                <span><span class="l">{{ __('Payroll Runs') }}</span><span class="v" style="display:block">{{ __('View all') }}</span></span>
            </a>
            <a href="{{ route('payroll.payslips.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-amber">&#128196;</span>
                <span><span class="l">{{ __('Payslips') }}</span><span class="v" style="display:block">{{ __('View all') }}</span></span>
            </a>
            <a href="{{ route('payroll.statutory.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-steel">&#127973;</span>
                <span><span class="l">{{ __('Statutory') }}</span><span class="v" style="display:block">{{ __('PAYE &amp; Pension') }}</span></span>
            </a>
            <a href="{{ route('payroll.people.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-ink">&#127973;</span>
                <span><span class="l">{{ __('People Ops') }}</span><span class="v" style="display:block">{{ __('Loans &amp; Leave') }}</span></span>
            </a>
            <a href="{{ route('payroll.reports.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-mint">&#128202;</span>
                <span><span class="l">{{ __('Reports') }}</span><span class="v" style="display:block">{{ __('Analytics') }}</span></span>
            </a>
            <a href="{{ route('payroll.settings.index') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-amber">&#9881;</span>
                <span><span class="l">{{ __('Settings') }}</span><span class="v" style="display:block">{{ __('Configuration') }}</span></span>
            </a>
            <a href="{{ route('payroll.runs.create') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-mint">&#9654;</span>
                <span><span class="l">{{ __('New Run') }}</span><span class="v" style="display:block">{{ __('Calculate pay') }}</span></span>
            </a>
            <a href="{{ route('payroll.employees.create') }}" class="pr-fbox">
                <span class="pr-fbox-t pr-t-steel">+</span>
                <span><span class="l">{{ __('Add Employee') }}</span><span class="v" style="display:block">{{ __('Onboard') }}</span></span>
            </a>
        </div>

        {{-- Last Run Card --}}
        @if($lastRun)
            <div class="pr-card" style="margin-bottom:16px">
                <div class="pr-card-h">
                    <h2>{{ __('Last Run') }} &mdash; {{ $lastRun->run_number ?? 'PR-' . $lastRun->id }}</h2>
                    <x-payroll::badge :status="$lastRun->status" type="run" />
                    <div class="right">
                        <a href="{{ route('payroll.payslips.index') }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Payslips') }}</a>
                        <a href="{{ route('payroll.runs.show', $lastRun) }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Audit') }}</a>
                    </div>
                </div>
                <div class="pr-pad" style="display:flex;gap:22px;flex-wrap:wrap;font-size:12.5px">
                    <div class="pr-fld"><div class="l">{{ __('Period') }}</div><div class="v">{{ $lastRun->pay_period_start?->format('d M') }} &ndash; {{ $lastRun->pay_period_end?->format('d M Y') }}</div></div>
                    <div class="pr-fld"><div class="l">{{ __('Paid') }}</div><div class="v">{{ $lastRun->items->count() }} {{ __('employees') }}</div></div>
                    <div class="pr-fld"><div class="l">{{ __('Gross') }}</div><div class="v">{{ format_number($grossPay) }}</div></div>
                    <div class="pr-fld"><div class="l">{{ __('Net') }}</div><div class="v">{{ format_number($netPay) }}</div></div>
                </div>
            </div>
        @endif

        {{-- Recent Payslips --}}
        <div class="pr-card">
            <div class="pr-card-h">
                <h2>{{ __('Recent Payslips') }}</h2>
                <div class="right">
                    <a href="{{ route('payroll.payslips.index') }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('View all') }} &rarr;</a>
                </div>
            </div>
            <div class="pr-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Run') }}</th>
                            <th class="num">{{ __('Gross') }}</th>
                            <th class="num">{{ __('Deductions') }}</th>
                            <th class="num">{{ __('Net Pay') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayslips as $payslip)
                            <tr>
                                <td style="font-weight:700;color:var(--ink)">{{ $payslip->employee?->first_name }} {{ $payslip->employee?->last_name }}</td>
                                <td class="pr-mono">{{ $payslip->payrollRun?->run_number ?? 'PR-' . ($payslip->payrollRun?->id ?? '—') }}</td>
                                <td class="pr-numr">{{ format_number($payslip->gross_pay) }}</td>
                                <td class="pr-numr red">{{ format_number($payslip->total_deductions) }}</td>
                                <td class="pr-numr green">{{ format_number($payslip->net_pay) }}</td>
                                <td><x-payroll::badge :status="$payslip->payrollRun?->status ?? 'draft'" type="run" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="pr-em" style="text-align:center;padding:24px">{{ __('No payslips yet. Run your first payroll to generate payslips.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
