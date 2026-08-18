<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        @php
            $runNumber = $run->run_number ?? 'PR-' . str_pad($run->id, 4, '0', STR_PAD_LEFT);
            $totalPaye = $run->total_paye ?? $run->items->sum('paye');
            $totalPensionEe = $run->total_pension_ee ?? $run->items->sum('pension_ee');
            $totalPensionEr = $run->total_pension_er ?? $run->items->sum('pension_er');
            $totalOtherDeductions = $totalDeductions - $totalPaye - $totalPensionEe;

            $workflowSteps = ['draft', 'calculated', 'approved', 'posted'];
            $workflowLabels = ['Draft', 'Calculated', 'Approved', 'Posted'];
            $currentIdx = array_search($run->status, $workflowSteps) !== false
                ? array_search($run->status, $workflowSteps)
                : ($run->status === 'pending_approval' ? 1.5 : 4);

            $stepStates = [];
            foreach ($workflowSteps as $i => $step) {
                if ($i < $currentIdx) {
                    $stepStates[] = 'done';
                } elseif ($run->status === 'pending_approval' && $step === 'approved') {
                    $stepStates[] = 'cur';
                } elseif (is_float($currentIdx) && $i === (int) $currentIdx) {
                    $stepStates[] = 'done';
                } elseif ($i == $currentIdx) {
                    $stepStates[] = 'cur';
                } else {
                    $stepStates[] = 'todo';
                }
            }
        @endphp

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('payroll.dashboard') }}">{{ __('Payroll') }}</a> ›
            <a href="{{ route('payroll.runs.index') }}">{{ __('Runs') }}</a> ›
            <span class="here">{{ $runNumber }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ $runNumber }}<span style="margin-left:12px;vertical-align:middle"><x-payroll::badge type="run" :status="$run->status" /></span></h1>
                <div class="sub">{{ $run->period_label ?? $run->period_start->format('M d, Y') . ' – ' . $run->period_end->format('M d, Y') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                @if($run->status === 'draft')
                    <form method="POST" action="{{ route('payroll.runs.calculate', $run) }}">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Calculate') }}</button>
                    </form>
                @endif
                @if($run->status === 'calculated')
                    <form method="POST" action="{{ route('payroll.runs.submit', $run) }}">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-sec pr-btn-sm">{{ __('Submit for Approval') }}</button>
                    </form>
                @endif
                @if($run->status === 'pending_approval')
                    <form method="POST" action="{{ route('payroll.runs.approve', $run) }}">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-sec pr-btn-sm">{{ __('Approve') }}</button>
                    </form>
                @endif
                @if($run->status === 'approved')
                    <form method="POST" action="{{ route('payroll.runs.post', $run) }}">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Post to GL') }}</button>
                    </form>
                @endif
                @if($run->status === 'posted' || $run->status === 'partially_paid')
                    <form method="POST" action="{{ route('payroll.runs.pay', $run) }}">
                        @csrf
                        <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Record Payment') }}</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Workflow strip --}}
        <div class="pr-wflow">
            @foreach($workflowSteps as $i => $step)
                <span class="pr-wf {{ $stepStates[$i] }}">
                    @if($stepStates[$i] === 'done') ✓ @endif
                    {{ $workflowLabels[$i] }}
                    @if($run->status === 'pending_approval' && $step === 'calculated')
                        <span style="font-size:9px;font-weight:600;margin-left:2px;opacity:.7">({{ __('submitted') }})</span>
                    @endif
                </span>
                @if(!$loop->last)
                    <span class="pr-wf-arr">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                @endif
            @endforeach
        </div>

        {{-- KPI strip --}}
        <div class="pr-kpis" style="margin-bottom:16px">
            <div class="pr-kpi pr-kpi-hero">
                <div class="l">{{ __('Total Gross') }}</div>
                <div class="v">{{ format_number($totalGross) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('PAYE') }}</div>
                <div class="v" style="color:var(--red-2)">{{ format_number($totalPaye) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Pension EE') }}</div>
                <div class="v" style="color:var(--red-2)">{{ format_number($totalPensionEe) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Pension ER') }}</div>
                <div class="v">{{ format_number($totalPensionEr) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Deductions') }}</div>
                <div class="v" style="color:var(--red-2)">{{ format_number($totalDeductions) }}</div>
            </div>
            <div class="pr-kpi pr-kpi-hero">
                <div class="l">{{ __('Net Pay') }}</div>
                <div class="v">{{ format_number($totalNetPay) }}</div>
            </div>
        </div>

        {{-- Run detail card --}}
        <div class="pr-card" style="margin-bottom:16px">
            <div class="pr-card-h">
                <h2>{{ __('Run Details') }}</h2>
                <div class="right">
                    @if($run->payeTable)
                        <span class="pr-mono-chip">{{ __('PAYE: ') }}{{ $run->payeTable->name }}</span>
                    @endif
                    @if($run->pensionScheme)
                        <span class="pr-mono-chip">{{ __('Pension: ') }}{{ $run->pensionScheme->name }}</span>
                    @endif
                </div>
            </div>
            <div class="pr-pad" style="display:flex;gap:22px;flex-wrap:wrap;font-size:12.5px">
                <div class="pr-fld">
                    <div class="l">{{ __('Period') }}</div>
                    <div class="v">{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</div>
                </div>
                <div class="pr-fld">
                    <div class="l">{{ __('Payment Date') }}</div>
                    <div class="v">{{ $run->pay_date?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="pr-fld">
                    <div class="l">{{ __('Employees') }}</div>
                    <div class="v">{{ $employeeCount }}</div>
                </div>
                <div class="pr-fld">
                    <div class="l">{{ __('Created By') }}</div>
                    <div class="v">{{ $run->createdBy?->name ?? '—' }}</div>
                </div>
                @if($run->approvedByUser)
                    <div class="pr-fld">
                        <div class="l">{{ __('Approved By') }}</div>
                        <div class="v">{{ $run->approvedByUser->name }}</div>
                    </div>
                @endif
                @if($run->approved_at)
                    <div class="pr-fld">
                        <div class="l">{{ __('Approved At') }}</div>
                        <div class="v">{{ $run->approved_at->format('d M Y H:i') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Employee items table --}}
        <div class="pr-card">
            <div class="pr-card-h">
                <h2>{{ __('Employee Details') }}</h2>
                <div class="right">
                    <span style="font-size:12px;color:var(--muted)">{{ $employeeCount }} {{ __('employees') }}</span>
                </div>
            </div>
            <div class="pr-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Employee #') }}</th>
                            <th class="num">{{ __('Basic Pay') }}</th>
                            <th class="num">{{ __('Allowances') }}</th>
                            <th class="num">{{ __('Gross') }}</th>
                            <th class="num">{{ __('PAYE') }}</th>
                            <th class="num">{{ __('Pension EE') }}</th>
                            <th class="num">{{ __('Deductions') }}</th>
                            <th class="num">{{ __('Net Pay') }}</th>
                            <th class="num">{{ __('Pension ER') }}</th>
                            <th style="text-align:right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($run->items as $item)
                            @php
                                $emp = $item->employee;
                            @endphp
                            <tr>
                                <td style="font-weight:700;color:var(--ink)">{{ $emp?->full_name ?? '—' }}</td>
                                <td class="pr-mono">{{ $emp?->employee_number ?? '—' }}</td>
                                <td class="pr-numr">{{ format_number($item->basic_pay) }}</td>
                                <td class="pr-numr">{{ format_number($item->total_allowances) }}</td>
                                <td class="pr-numr bold">{{ format_number($item->gross_pay) }}</td>
                                <td class="pr-numr red">{{ format_number($item->paye) }}</td>
                                <td class="pr-numr red">{{ format_number($item->pension_ee) }}</td>
                                <td class="pr-numr {{ $item->total_deductions > 0 ? 'red' : 'dash' }}">{{ format_number($item->total_deductions) }}</td>
                                <td class="pr-numr green bold">{{ format_number($item->net_pay) }}</td>
                                <td class="pr-numr">{{ format_number($item->pension_er) }}</td>
                                <td class="pr-row-act">
                                    <a href="{{ route('payroll.payslips.show', $item) }}" class="pr-ibtn" title="{{ __('View Payslip') }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="text-align:center;padding:32px;color:var(--muted)">
                                    {{ __('No employee items in this run.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($run->items->count() > 0)
                        <tfoot>
                            <tr>
                                <td colspan="2">{{ __('Totals') }} — {{ $employeeCount }} {{ __('employees') }}</td>
                                <td class="pr-numr">{{ format_number($run->items->sum('basic_pay')) }}</td>
                                <td class="pr-numr">{{ format_number($run->items->sum('total_allowances')) }}</td>
                                <td class="pr-numr">{{ format_number($totalGross) }}</td>
                                <td class="pr-numr">{{ format_number($totalPaye) }}</td>
                                <td class="pr-numr">{{ format_number($totalPensionEe) }}</td>
                                <td class="pr-numr">{{ format_number($totalDeductions) }}</td>
                                <td class="pr-numr">{{ format_number($totalNetPay) }}</td>
                                <td class="pr-numr">{{ format_number($totalPensionEr) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
