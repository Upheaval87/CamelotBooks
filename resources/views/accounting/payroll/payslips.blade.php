<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('payroll.dashboard') }}">{{ __('Payroll') }}</a> ›
            <span class="here">{{ __('Payslips') }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ __('Payslips') }}</h1>
                <div class="sub">{{ __('View, email, and print employee payslips.') }}</div>
            </div>
        </div>

        @php
            $totalPayslips = $payslips->total();
            $paidCount = $payslips->getCollection()->where('status', 'paid')->count();
            $pendingCount = $payslips->getCollection()->where('status', 'pending')->count();
        @endphp

        {{-- KPI strip --}}
        <div class="pr-kpis" style="margin-bottom:16px">
            <div class="pr-kpi pr-kpi-hero">
                <div class="l">{{ __('Total Payslips') }}</div>
                <div class="v">{{ format_number($totalPayslips) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Paid') }}</div>
                <div class="v">{{ format_number($paidCount) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Pending') }}</div>
                <div class="v">{{ format_number($pendingCount) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Total Net Pay') }}</div>
                <div class="v">{{ format_number($payslips->getCollection()->sum('net_pay')) }}</div>
            </div>
        </div>

        {{-- Filter controls --}}
        <div class="pr-card" style="margin-bottom:16px">
            <div class="pr-pad" style="padding-bottom:0">
                <form method="GET" action="{{ route('payroll.payslips.index') }}">
                    <div class="pr-controls">
                        <div class="pr-search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input class="pr-input" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search payslips...') }}">
                        </div>
                        <select class="pr-input" name="status" style="width:auto">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('Sent') }}</option>
                        </select>
                        <button type="submit" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Filter') }}</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('payroll.payslips.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Payslips table --}}
        <div class="pr-card">
            <div class="pr-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Employee #') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Run') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Gross') }}</th>
                            <th class="num">{{ __('Deductions') }}</th>
                            <th class="num">{{ __('Net Pay') }}</th>
                            <th style="text-align:right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $payslip)
                            @php
                                $emp = $payslip->employee;
                            @endphp
                            <tr>
                                <td style="font-weight:700;color:var(--ink)">{{ $emp?->full_name ?? '—' }}</td>
                                <td class="pr-mono">{{ $emp?->employee_number ?? '—' }}</td>
                                <td class="pr-em">{{ $payslip->period_label ?? $payslip->payrollRun?->period_label ?? '—' }}</td>
                                <td class="pr-mono">
                                    @if($payslip->payroll_run_id)
                                        <a href="{{ route('payroll.runs.show', $payslip->payroll_run_id) }}" style="color:var(--sec);text-decoration:none">{{ $payslip->payrollRun?->run_number ?? 'PR-' . str_pad($payslip->payroll_run_id, 4, '0', STR_PAD_LEFT) }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-payroll::badge :status="$payslip->status ?? 'draft'" /></td>
                                <td class="pr-numr">{{ format_number($payslip->gross_pay) }}</td>
                                <td class="pr-numr {{ ($payslip->total_deductions ?? 0) > 0 ? 'red' : 'dash' }}">{{ format_number($payslip->total_deductions ?? 0) }}</td>
                                <td class="pr-numr green bold">{{ format_number($payslip->net_pay) }}</td>
                                <td class="pr-row-act">
                                    <a href="{{ route('payroll.payslips.show', $payslip) }}" class="pr-ibtn" title="{{ __('View') }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @if(($payslip->status ?? '') !== 'sent')
                                        <form method="POST" action="{{ route('payroll.payslips.send', $payslip) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pr-ibtn" title="{{ __('Email') }}">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:32px;color:var(--muted)">
                                    {{ __('No payslips found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payslips->hasPages())
                <div class="pr-pagi">
                    <span class="t">{{ __('Showing') }} {{ $payslips->firstItem() }}–{{ $payslips->lastItem() }} {{ __('of') }} {{ $payslips->total() }} {{ __('payslips') }}</span>
                    <div style="display:flex;gap:8px">
                        @if($payslips->onFirstPage())
                            <span class="pr-btn pr-btn-ghost pr-btn-xs" style="opacity:.5">{{ __('← Prev') }}</span>
                        @else
                            <a href="{{ $payslips->previousPageUrl() }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('← Prev') }}</a>
                        @endif
                        @if($payslips->hasMorePages())
                            <a href="{{ $payslips->nextPageUrl() }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Next →') }}</a>
                        @else
                            <span class="pr-btn pr-btn-ghost pr-btn-xs" style="opacity:.5">{{ __('Next →') }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
