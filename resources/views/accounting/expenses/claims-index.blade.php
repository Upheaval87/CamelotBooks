<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @php
        $badgeMap = [
            'draft' => 'b-draft', 'pending' => 'b-pend', 'approved' => 'b-app',
            'rejected' => 'b-rej', 'reimbursed' => 'b-paid',
        ];
    @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('Expense Claims') }}</h1>
                <div class="sub">{{ __('Employee claims — submit, approve and reimburse.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <details class="more">
                    <summary class="btn btn-ghost btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 9h12M6 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ __('More') }}
                    </summary>
                    <div class="more-menu">
                        <a class="more-item" href="{{ route('accounting.expenses.dashboard') }}">{{ __('Expense Dashboard') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.index') }}">{{ __('All Expenses') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.reports') }}">{{ __('Reports') }}</a>
                    </div>
                </details>
                <a href="{{ route('accounting.expenses.claims.create') }}" class="btn btn-cta btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('New Claim') }}
                </a>
            </div>
        </div>

        <section class="card">
            <div class="card-sec">
                <div class="statgrid">
                    <a href="{{ route('accounting.expenses.claims.index') }}" class="fbox {{ !request('status') ? 'on' : '' }}">
                        <span class="t t-ink"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 9h18" stroke="currentColor" stroke-width="2"/></svg></span>
                        <span><span class="l">All</span><span class="v" style="display:block">{{ $stats['all'] }}</span></span>
                    </a>
                    <a href="{{ route('accounting.expenses.claims.index', ['status' => 'draft']) }}" class="fbox {{ request('status') === 'draft' ? 'on' : '' }}">
                        <span class="t t-gray"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <span><span class="l">Draft</span><span class="v" style="display:block">{{ $stats['draft'] }}</span></span>
                    </a>
                    <a href="{{ route('accounting.expenses.claims.index', ['status' => 'pending']) }}" class="fbox {{ request('status') === 'pending' ? 'on' : '' }}">
                        <span class="t t-amber"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <span><span class="l">Pending</span><span class="v" style="display:block">{{ $stats['pending'] }}</span></span>
                    </a>
                    <a href="{{ route('accounting.expenses.claims.index', ['status' => 'approved']) }}" class="fbox {{ request('status') === 'approved' ? 'on' : '' }}">
                        <span class="t t-mint"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><span class="l">Approved</span><span class="v" style="display:block">{{ $stats['approved'] }}</span></span>
                    </a>
                    <a href="{{ route('accounting.expenses.claims.index', ['status' => 'rejected']) }}" class="fbox {{ request('status') === 'rejected' ? 'on' : '' }}">
                        <span class="t t-red"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <span><span class="l">Rejected</span><span class="v" style="display:block">{{ $stats['rejected'] }}</span></span>
                    </a>
                    <a href="{{ route('accounting.expenses.claims.index', ['status' => 'reimbursed']) }}" class="fbox {{ request('status') === 'reimbursed' ? 'on' : '' }}">
                        <span class="t t-green"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="2"/></svg></span>
                        <span><span class="l">Reimbursed</span><span class="v" style="display:block">{{ $stats['reimbursed'] }}</span></span>
                    </a>
                </div>

                <form method="GET" action="{{ route('accounting.expenses.claims.index') }}" class="controls">
                    <div class="search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <input class="input" name="q" value="{{ request('q') }}" placeholder="{{ __('Claim #, description or employee.') }}">
                    </div>
                    <button class="btn btn-ghost btn-xs" type="submit">{{ __('Filter') }}</button>
                    @if(request()->hasAny(['q', 'status']))
                        <a href="{{ route('accounting.expenses.claims.index') }}" class="btn btn-ghost btn-xs">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="card-sec" style="padding-top:6px">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:11%">{{ __('Claim #') }}</th>
                                <th style="width:9%">{{ __('Date') }}</th>
                                <th style="width:20%">{{ __('Employee') }}</th>
                                <th style="width:24%">{{ __('Description') }}</th>
                                <th class="num" style="width:10%">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:10%">{{ __('Status') }}</th>
                                <th style="width:16%">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($claims as $claim)
                                <tr>
                                    <td><a class="mono" href="{{ route('accounting.expenses.claims.show', $claim) }}">{{ $claim->claim_number }}</a></td>
                                    <td class="em">{{ $claim->expense_date?->format('M d') }}</td>
                                    <td class="em">{{ $claim->employee?->full_name ?? '—' }}</td>
                                    <td class="em">{{ $claim->description ?? '—' }}</td>
                                    <td class="numr">{{ format_number($claim->amount) }}</td>
                                    <td>
                                        <span class="badge {{ $badgeMap[$claim->status] ?? 'b-draft' }}"><span class="bdot"></span>{{ $claim->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            <a class="btn btn-sec btn-xs" href="{{ route('accounting.expenses.claims.show', $claim) }}">{{ __('View') }}</a>
                                            @if($claim->isDraft())
                                                @can('expense-claims.submit')
                                                    <form method="POST" action="{{ route('accounting.expenses.claims.submit', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Submit this claim for approval?') }}', { type: 'action' })">
                                                        @csrf
                                                        <button class="btn btn-cta btn-xs" type="submit">{{ __('Submit') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expense-claims.delete')
                                                    <form method="POST" action="{{ route('accounting.expenses.claims.destroy', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this claim?') }}', { type: 'danger' })">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-ghost btn-xs" type="submit">{{ __('Delete') }}</button>
                                                    </form>
                                                @endcan
                                            @elseif($claim->isPending())
                                                @can('expense-claims.approve')
                                                    <form method="POST" action="{{ route('accounting.expenses.claims.approve', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Approve this claim?') }}', { type: 'action' })">
                                                        @csrf
                                                        <button class="btn btn-cta btn-xs" type="submit">{{ __('Approve') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expense-claims.reject')
                                                    <form method="POST" action="{{ route('accounting.expenses.claims.reject', $claim) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for rejecting this claim:') }}', { confirmLabel: '{{ __('Reject') }}', type: 'danger' })">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="" />
                                                        <button class="btn btn-ghost btn-xs" type="submit">{{ __('Reject') }}</button>
                                                    </form>
                                                @endcan
                                            @elseif($claim->isApproved())
                                                @if($claim->expense)
                                                    <a class="btn btn-sec btn-xs" href="{{ route('accounting.expenses.show', $claim->expense) }}">{{ __('View Expense') }}</a>
                                                @endif
                                                @can('expense-claims.reimburse')
                                                    <form method="POST" action="{{ route('accounting.expenses.claims.reimburse', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Mark this claim as reimbursed?') }}', { type: 'action' })">
                                                        @csrf
                                                        <button class="btn btn-cta btn-xs" type="submit">{{ __('Reimburse') }}</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="em" style="text-align:center;padding:28px">{{ __('No expense claims found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($claims->hasPages() || $claims->total() > 0)
                <div class="pagi">
                    <span class="t">{{ __('Showing') }} {{ $claims->firstItem() ?? 0 }}–{{ $claims->lastItem() ?? 0 }} {{ __('of') }} {{ $claims->total() }} {{ __('claims') }}</span>
                    <div style="display:flex;gap:8px">
                        <a href="{{ $claims->previousPageUrl() }}" class="btn btn-ghost btn-sm {{ $claims->onFirstPage() ? 'is-disabled' : '' }}">← Prev</a>
                        <a href="{{ $claims->nextPageUrl() }}" class="btn btn-ghost btn-sm {{ $claims->hasMorePages() ? '' : 'is-disabled' }}">Next →</a>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
