<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('payroll.dashboard') }}">{{ __('Payroll') }}</a> ›
            <span class="here">{{ __('Runs') }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ __('Payroll Runs') }}</h1>
                <div class="sub">{{ __('Calculate, approve, post and generate payslips.') }}</div>
            </div>
            <a href="{{ route('payroll.runs.create') }}" class="pr-btn pr-btn-cta pr-btn-sm">+ {{ __('New Run') }}</a>
        </div>

        @php
            $totalRuns = $runs->total();
            $draftCount = $runs->getCollection()->where('status', 'draft')->count();
            $approvedCount = $runs->getCollection()->where('status', 'approved')->count();
            $paidCount = $runs->getCollection()->whereIn('status', ['partially_paid', 'fully_paid'])->count();
        @endphp

        {{-- KPI strip --}}
        <div class="pr-kpis" style="margin-bottom:16px">
            <div class="pr-kpi pr-kpi-hero">
                <div class="l">{{ __('Total Runs') }}</div>
                <div class="v">{{ format_number($totalRuns) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Draft') }}</div>
                <div class="v">{{ format_number($draftCount) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Approved') }}</div>
                <div class="v">{{ format_number($approvedCount) }}</div>
            </div>
            <div class="pr-kpi">
                <div class="l">{{ __('Paid') }}</div>
                <div class="v">{{ format_number($paidCount) }}</div>
            </div>
        </div>

        {{-- Filter controls --}}
        <div class="pr-card" style="margin-bottom:16px">
            <div class="pr-pad" style="padding-bottom:0">
                <form method="GET" action="{{ route('payroll.runs.index') }}">
                    <div class="pr-controls">
                        <div class="pr-search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input class="pr-input" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search runs...') }}">
                        </div>
                        <select class="pr-input" name="status" style="width:auto">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>{{ __('Calculated') }}</option>
                            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>{{ __('Pending Approval') }}</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>{{ __('Posted') }}</option>
                            <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>{{ __('Partially Paid') }}</option>
                            <option value="fully_paid" {{ request('status') === 'fully_paid' ? 'selected' : '' }}>{{ __('Fully Paid') }}</option>
                        </select>
                        <button type="submit" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Filter') }}</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('payroll.runs.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Runs table --}}
        <div class="pr-card">
            <div class="pr-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Run #') }}</th>
                            <th>{{ __('Period Label') }}</th>
                            <th>{{ __('Pay Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Total Gross') }}</th>
                            <th class="num">{{ __('Total Net') }}</th>
                            <th>{{ __('Created By') }}</th>
                            <th style="text-align:right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                            <tr>
                                <td class="pr-mono">{{ $run->run_number ?? 'PR-' . str_pad($run->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td style="font-weight:600;color:var(--ink)">{{ $run->period_label ?? $run->period_start->format('M Y') }}</td>
                                <td class="pr-em">{{ $run->pay_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-payroll::badge type="run" :status="$run->status" /></td>
                                <td class="pr-numr bold">{{ format_number($run->total_gross ?? $run->items->sum('gross_pay')) }}</td>
                                <td class="pr-numr bold">{{ format_number($run->total_net_pay ?? $run->items->sum('net_pay')) }}</td>
                                <td class="pr-em">{{ $run->createdBy?->name ?? '—' }}</td>
                                <td class="pr-row-act">
                                    <a href="{{ route('payroll.runs.show', $run) }}" class="pr-ibtn" title="{{ __('View') }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @if($run->status === 'pending_approval')
                                        <form method="POST" action="{{ route('payroll.runs.approve', $run) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pr-ibtn" title="{{ __('Approve') }}" style="color:var(--green)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if($run->status === 'approved')
                                        <form method="POST" action="{{ route('payroll.runs.post', $run) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pr-ibtn" title="{{ __('Post to GL') }}">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if($run->status === 'posted')
                                        <form method="POST" action="{{ route('payroll.runs.pay', $run) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pr-ibtn" title="{{ __('Record Payment') }}">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">
                                    {{ __('No payroll runs found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($runs->hasPages())
                <div class="pr-pagi">
                    <span class="t">{{ __('Showing') }} {{ $runs->firstItem() }}–{{ $runs->lastItem() }} {{ __('of') }} {{ $runs->total() }} {{ __('runs') }}</span>
                    <div style="display:flex;gap:8px">
                        @if($runs->onFirstPage())
                            <span class="pr-btn pr-btn-ghost pr-btn-xs" style="opacity:.5">{{ __('← Prev') }}</span>
                        @else
                            <a href="{{ $runs->previousPageUrl() }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('← Prev') }}</a>
                        @endif
                        @if($runs->hasMorePages())
                            <a href="{{ $runs->nextPageUrl() }}" class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Next →') }}</a>
                        @else
                            <span class="pr-btn pr-btn-ghost pr-btn-xs" style="opacity:.5">{{ __('Next →') }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
