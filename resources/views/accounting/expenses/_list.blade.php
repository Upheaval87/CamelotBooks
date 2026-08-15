@php
    $badgeMap = [
        'draft' => 'b-draft', 'pending' => 'b-pend', 'approved' => 'b-app', 'posted' => 'b-post',
        'paid' => 'b-paid', 'rejected' => 'b-rej', 'returned' => 'b-ret', 'void' => 'b-void',
    ];
    $depts = collect($expenses->items())->pluck('department')->filter()->unique()->sort()->values();
@endphp

<section class="card">
    <div class="card-sec">
        <div class="statgrid">
            <a href="{{ route('accounting.expenses.index') }}" class="fbox {{ !request('status') ? 'on' : '' }}">
                <span class="t t-ink"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 9h18" stroke="currentColor" stroke-width="2"/></svg></span>
                <span><span class="l">All</span><span class="v" style="display:block">{{ $stats['all'] }}</span></span>
            </a>
            <a href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}" class="fbox {{ request('status') === 'pending' ? 'on' : '' }}">
                <span class="t t-amber"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <span><span class="l">Pending</span><span class="v" style="display:block">{{ $stats['pending'] }}</span></span>
            </a>
            <a href="{{ route('accounting.expenses.index', ['status' => 'approved']) }}" class="fbox {{ request('status') === 'approved' ? 'on' : '' }}">
                <span class="t t-mint"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span><span class="l">Approved</span><span class="v" style="display:block">{{ $stats['approved'] }}</span></span>
            </a>
            <a href="{{ route('accounting.expenses.index', ['status' => 'posted']) }}" class="fbox {{ request('status') === 'posted' ? 'on' : '' }}">
                <span class="t t-teal"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                <span><span class="l">Posted</span><span class="v" style="display:block">{{ $stats['posted'] }}</span></span>
            </a>
            <a href="{{ route('accounting.expenses.index', ['status' => 'paid']) }}" class="fbox {{ request('status') === 'paid' ? 'on' : '' }}">
                <span class="t t-green"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="2"/></svg></span>
                <span><span class="l">Paid</span><span class="v" style="display:block">{{ $stats['paid'] }}</span></span>
            </a>
            <a href="{{ route('accounting.expenses.index', ['status' => 'rejected']) }}" class="fbox {{ request('status') === 'rejected' ? 'on' : '' }}">
                <span class="t t-red"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <span><span class="l">Rejected</span><span class="v" style="display:block">{{ $stats['rejected'] }}</span></span>
            </a>
        </div>

        <form method="GET" action="{{ route('accounting.expenses.index') }}" class="controls">
            <div class="search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input class="input" name="q" value="{{ request('q') }}" placeholder="{{ __('Expense #, description, payee, invoice #, employee.') }}">
            </div>
            <select class="input" name="category_id">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select class="input" name="department">
                <option value="">{{ __('All Departments') }}</option>
                @foreach($depts as $d)
                    <option value="{{ $d }}" {{ request('department') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>
            <select class="input" name="payment_status">
                <option value="">{{ __('Payment: All') }}</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
                <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
            </select>
            <button class="btn btn-ghost btn-xs" type="submit">{{ __('Filter') }}</button>
            @if(request()->hasAny(['q', 'category_id', 'department', 'payment_status', 'status', 'from_date', 'to_date']))
                <a href="{{ route('accounting.expenses.index') }}" class="btn btn-ghost btn-xs">{{ __('Clear') }}</a>
            @endif
        </form>
    </div>

    <div class="card-sec" style="padding-top:6px">
        <div class="li-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:11%">{{ __('Expense #') }}</th>
                        <th style="width:9%">{{ __('Date') }}</th>
                        <th style="width:20%">{{ __('Description') }}</th>
                        <th style="width:13%">{{ __('Category') }}</th>
                        <th style="width:12%">{{ __('Payee') }}</th>
                        <th style="width:10%">{{ __('Department') }}</th>
                        <th class="num" style="width:9%">{{ __('Amount') }} ({{ $cs }})</th>
                        <th style="width:10%">{{ __('Status') }}</th>
                        <th style="width:6%"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td><a class="mono" href="{{ route('accounting.expenses.show', $expense) }}">{{ $expense->expense_number }}</a></td>
                            <td class="em">{{ $expense->expense_date?->format('M d') }}</td>
                            <td class="em">{{ $expense->memo }}</td>
                            <td class="em">{{ $expense->category?->name ?? '—' }}</td>
                            <td class="em">{{ $expense->vendor?->name ?? '—' }}</td>
                            <td class="em">{{ $expense->department ?? '—' }}</td>
                            <td class="numr">{{ format_number($expense->amount) }}</td>
                            <td>
                                <span class="badge {{ $badgeMap[$expense->status] ?? 'b-draft' }}"><span class="bdot"></span>{{ $expense->statusLabel() }}</span>
                            </td>
                            <td>
                                <div class="row-act">
                                    <details class="more">
                                        <summary class="ibtn" aria-label="{{ __('Actions') }}">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="5" cy="12" r="1.6" fill="currentColor"/><circle cx="12" cy="12" r="1.6" fill="currentColor"/><circle cx="19" cy="12" r="1.6" fill="currentColor"/></svg>
                                        </summary>
                                        <div class="more-menu">
                                            <a class="more-item" href="{{ route('accounting.expenses.show', $expense) }}">{{ __('View') }}</a>
                                            @if(in_array($expense->status, ['draft', 'returned']))
                                                @can('expenses.edit')
                                                    <a class="more-item" href="{{ route('accounting.expenses.edit', $expense) }}">{{ __('Edit') }}</a>
                                                @endcan
                                                @can('expenses.submit')
                                                    <form method="POST" action="{{ route('accounting.expenses.submit', $expense) }}">
                                                        @csrf
                                                        <button class="more-item" type="submit">{{ __('Submit') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.duplicate')
                                                    <form method="POST" action="{{ route('accounting.expenses.duplicate', $expense) }}">
                                                        @csrf
                                                        <button class="more-item" type="submit">{{ __('Duplicate') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.delete')
                                                    <form method="POST" action="{{ route('accounting.expenses.destroy', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this expense?') }}', { type: 'danger' })">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="more-item danger" type="submit">{{ __('Delete') }}</button>
                                                    </form>
                                                @endcan
                                            @elseif($expense->status === 'pending')
                                                @can('expenses.approve')
                                                    <form method="POST" action="{{ route('accounting.expenses.approve', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Approve this expense?') }}', { type: 'action' })">
                                                        @csrf
                                                        <button class="more-item" type="submit">{{ __('Approve') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.reject')
                                                    <form method="POST" action="{{ route('accounting.expenses.reject', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for rejection:') }}', { confirmLabel: '{{ __('Reject') }}', type: 'danger' })">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="" />
                                                        <button class="more-item danger" type="submit">{{ __('Reject') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.return')
                                                    <form method="POST" action="{{ route('accounting.expenses.return', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for returning for correction:') }}', { confirmLabel: '{{ __('Return') }}' })">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="" />
                                                        <button class="more-item" type="submit">{{ __('Return for Correction') }}</button>
                                                    </form>
                                                @endcan
                                            @elseif($expense->status === 'approved')
                                                @can('expenses.post')
                                                    <form method="POST" action="{{ route('accounting.expenses.post', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Post this expense?') }}', { type: 'action' })">
                                                        @csrf
                                                        <button class="more-item" type="submit">{{ __('Post') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.edit')
                                                    <a class="more-item" href="{{ route('accounting.expenses.edit', $expense) }}">{{ __('Edit') }}</a>
                                                @endcan
                                            @elseif(in_array($expense->status, ['posted', 'paid']))
                                                @if($expense->journalEntry)
                                                    <a class="more-item" href="{{ route('accounting.journal-entries.show', $expense->journalEntry) }}">{{ __('View Journal') }}</a>
                                                @endif
                                                @can('expenses.pay')
                                                    @if($expense->status === 'posted')
                                                        <a class="more-item" href="{{ route('accounting.expenses.show', $expense) }}">{{ __('Record Payment') }}</a>
                                                    @endif
                                                @endcan
                                                @can('expenses.duplicate')
                                                    <form method="POST" action="{{ route('accounting.expenses.duplicate', $expense) }}">
                                                        @csrf
                                                        <button class="more-item" type="submit">{{ __('Duplicate') }}</button>
                                                    </form>
                                                @endcan
                                                @can('expenses.void')
                                                    <form method="POST" action="{{ route('accounting.expenses.void', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for voiding this expense:') }}', { confirmLabel: '{{ __('Void') }}', type: 'danger' })">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="" />
                                                        <button class="more-item danger" type="submit">{{ __('Reverse') }}</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="em" style="text-align:center;padding:28px">{{ __('No expenses found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($expenses->hasPages() || $expenses->total() > 0)
        <div class="pagi">
            <span class="t">{{ __('Showing') }} {{ $expenses->firstItem() ?? 0 }}–{{ $expenses->lastItem() ?? 0 }} {{ __('of') }} {{ $expenses->total() }} {{ __('expenses') }}</span>
            <div style="display:flex;gap:8px">
                <a href="{{ $expenses->previousPageUrl() }}" class="btn btn-ghost btn-sm {{ $expenses->onFirstPage() ? 'is-disabled' : '' }}">← Prev</a>
                <a href="{{ $expenses->nextPageUrl() }}" class="btn btn-ghost btn-sm {{ $expenses->hasMorePages() ? '' : 'is-disabled' }}">Next →</a>
            </div>
        </div>
    @endif
</section>
