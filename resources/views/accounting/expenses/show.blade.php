<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @php
        $badgeMap = [
            'draft' => 'b-draft', 'pending' => 'b-pend', 'approved' => 'b-app', 'posted' => 'b-post',
            'paid' => 'b-paid', 'rejected' => 'b-rej', 'returned' => 'b-ret', 'void' => 'b-void',
        ];
        $subtotal = round($expense->lines->sum('amount'), 2);
        $vat = round($expense->lines->sum('tax_amount'), 2);
        $discount = round($expense->lines->sum('discount'), 2);
        $journalEntry = $expense->journalEntry;
        $firstPayment = $expense->payments->first();
        $auditLabels = [
            'created' => 'Created expense',
            'updated' => 'Updated expense',
            'submitted' => 'Submitted for approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'returned' => 'Returned for correction',
            'posted' => 'Posted to general ledger',
            'paid' => 'Payment recorded',
            'voided' => 'Voided expense',
        ];
        $statusText = $expense->statusLabel();
    @endphp

    <div class="ex-suite wrap">
        <div class="sticky-head">
            <div style="display:flex;align-items:center;gap:10px">
                <a href="{{ route('accounting.expenses.index') }}" class="icon-btn" aria-label="{{ __('Back to expenses') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <nav class="crumbs">
                    <a href="{{ route('accounting.expenses.index') }}">{{ __('Expenses') }}</a>
                    <span class="sep">›</span>
                    <a href="{{ route('accounting.expenses.index') }}">{{ __('All Expenses') }}</a>
                    <span class="sep">›</span>
                    <span class="here">{{ $expense->expense_number }}</span>
                </nav>
            </div>
            <div class="cluster">
                <button onclick="window.print()" class="btn btn-ghost btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    {{ __('Print') }}
                </button>

                @if($journalEntry)
                    <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}" class="btn btn-ghost btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        {{ __('View Journal') }}
                    </a>
                @endif

                @if($firstPayment)
                    <a href="{{ $firstPayment->journalEntry ? route('accounting.journal-entries.show', $firstPayment->journalEntry) : '#payments' }}" class="btn btn-ghost btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8"/></svg>
                        {{ __('View Payment') }}
                    </a>
                @endif

                @if(in_array($expense->status, ['draft', 'returned']))
                    @can('expenses.edit')
                        <a href="{{ route('accounting.expenses.edit', $expense) }}" class="btn btn-ghost btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17v3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            {{ __('Edit') }}
                        </a>
                    @endcan
                    @can('expenses.submit')
                        <form method="POST" action="{{ route('accounting.expenses.submit', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Submit this expense for approval?') }}', { type: 'action' })">
                            @csrf
                            <button type="submit" class="btn btn-cta btn-sm">{{ __('Submit') }}</button>
                        </form>
                    @endcan
                @elseif($expense->status === 'pending')
                    @can('expenses.return')
                        <form method="POST" action="{{ route('accounting.expenses.return', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for returning for correction:') }}', { confirmLabel: '{{ __('Return') }}' })">
                            @csrf
                            <input type="hidden" name="reason" value="" />
                            <button type="submit" class="btn btn-ghost btn-sm">{{ __('Return for Correction') }}</button>
                        </form>
                    @endcan
                    @can('expenses.reject')
                        <form method="POST" action="{{ route('accounting.expenses.reject', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for rejection:') }}', { confirmLabel: '{{ __('Reject') }}', type: 'danger' })">
                            @csrf
                            <input type="hidden" name="reason" value="" />
                            <button type="submit" class="btn btn-danger-o btn-sm">{{ __('Reject') }}</button>
                        </form>
                    @endcan
                    @can('expenses.approve')
                        <form method="POST" action="{{ route('accounting.expenses.approve', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Approve this expense?') }}', { type: 'action' })">
                            @csrf
                            <button type="submit" class="btn btn-cta btn-sm">{{ __('Approve') }}</button>
                        </form>
                    @endcan
                @elseif($expense->status === 'approved')
                    @can('expenses.post')
                        <form method="POST" action="{{ route('accounting.expenses.post', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Post this expense?') }}', { type: 'action' })">
                            @csrf
                            <button type="submit" class="btn btn-cta btn-sm">{{ __('Post') }}</button>
                        </form>
                    @endcan
                @elseif(in_array($expense->status, ['posted', 'paid']))
                    @can('expenses.pay')
                        <a href="#payments" class="btn btn-ghost btn-sm">{{ __('Record Payment') }}</a>
                    @endcan
                @endif

                <div class="more">
                    <details>
                        <summary class="btn btn-ghost btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="5" cy="12" r="1.7" fill="currentColor"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/><circle cx="19" cy="12" r="1.7" fill="currentColor"/></svg>
                            {{ __('More') }}
                        </summary>
                        <div class="more-menu">
                            @can('expenses.duplicate')
                                <form method="POST" action="{{ route('accounting.expenses.duplicate', $expense) }}">
                                    @csrf
                                    <button class="more-item" type="submit">{{ __('Duplicate') }}</button>
                                </form>
                            @endcan
                            <a class="more-item" href="#audit">{{ __('Audit Trail') }}</a>
                            @if(in_array($expense->status, ['draft', 'returned']))
                                @can('expenses.delete')
                                    <form method="POST" action="{{ route('accounting.expenses.destroy', $expense) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this expense?') }}', { type: 'danger' })">
                                        @csrf
                                        @method('DELETE')
                                        <button class="more-item danger" type="submit">{{ __('Delete') }}</button>
                                    </form>
                                @endcan
                            @elseif(!in_array($expense->status, ['void']))
                                @can('expenses.void')
                                    <form method="POST" action="{{ route('accounting.expenses.void', $expense) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for voiding this expense:') }}', { confirmLabel: '{{ __('Void') }}', type: 'danger' })">
                                        @csrf
                                        <input type="hidden" name="reason" value="" />
                                        <button class="more-item danger" type="submit">{{ __('Reverse') }}</button>
                                    </form>
                                @endcan
                            @endif
                            <a class="more-item" href="{{ route('accounting.expenses.index') }}">{{ __('Back to Expenses') }}</a>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:20px">
            <section class="card">
                <div class="prof">
                    <span class="ava-xl">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <div>
                        <div class="n">
                            {{ __('Expense') }}
                            <span class="mono-chip">{{ $expense->expense_number }}</span>
                            <span class="badge {{ $badgeMap[$expense->status] ?? 'b-draft' }}"><span class="bdot"></span>{{ $statusText }}</span>
                            @if($expense->status === 'paid')
                                <span class="badge b-paid"><span class="bdot"></span>{{ __('Paid') }}</span>
                            @endif
                        </div>
                        <div class="c">
                            <span>{{ $expense->category?->name ?? '—' }} · {{ $expense->memo ?? '—' }}</span>
                            <span>{{ __('Payee') }} · {{ $expense->vendor?->name ?? '—' }}</span>
                            <span>{{ $expense->department ?? '—' }} · {{ $expense->costCenter?->name ?? $expense->branch?->name ?? '—' }}</span>
                            <span>{{ __('Ref') }} · {{ $expense->reference ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="sumbar" aria-label="{{ __('Amount') }}">
                <div class="cell"><div class="l">{{ __('Subtotal') }}</div><div class="v">{{ format_number($subtotal) }}</div></div>
                <div class="cell"><div class="l">{{ __('VAT') }}</div><div class="v">{{ format_number($vat) }}</div></div>
                <div class="cell"><div class="l">{{ __('Discount') }}</div><div class="v">{{ format_number(-$discount) }}</div></div>
                <div class="cell hero"><div class="l">{{ __('Total') }}</div><div class="v">{{ $cs }}{{ format_number($expense->amount) }}</div></div>
            </div>

            <section class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 3v18M5 7h14M5 7l-3 6a3.5 3.5 0 0 0 6 0l-3-6zM19 7l-3 6a3.5 3.5 0 0 0 6 0l-3-6z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <h2>{{ __('Allocation') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:40%">{{ __('Account') }}</th>
                                    <th style="width:25%">{{ __('Department / Cost Centre') }}</th>
                                    <th class="num" style="width:25%">{{ __('Amount') }} ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expense->lines as $line)
                                    <tr>
                                        <td style="font-weight:600;color:var(--ink)">
                                            @if($line->expenseAccount)
                                                <a href="{{ route('accounting.general-ledger.account', $line->expenseAccount->id) }}" class="acct-link">{{ $line->expenseAccount->name }}</a>
                                            @else
                                                {{ $line->description ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="em">
                                            {{ trim(($line->department ?? '') . ($line->costCenter ? ' · ' . $line->costCenter->name : '')) ?: '—' }}
                                        </td>
                                        <td class="numr">{{ format_number($line->line_total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="em" style="text-align:center;padding:24px">{{ __('No line items.') }}</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr><td colspan="2">{{ __('Total') }}</td><td class="numr">{{ format_number($expense->amount) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                        </span>
                        <h2>{{ __('Approval Workflow') }}</h2>
                        <span class="rule"></span>
                        @if(in_array($expense->status, ['posted', 'paid']))
                            <span class="fmt" style="margin-left:auto">locked · {{ strtolower($statusText) }}</span>
                        @endif
                    </div>
                    <div class="steps">
                        @foreach($workflow as $step)
                            <div class="step">
                                <span class="sdot {{ $step['state'] }}">
                                    @if($step['state'] === 'done')
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <div class="tt">{{ $step['title'] }}</div>
                                    <div class="mm">{{ $step['meta'] }}</div>
                                </div>
                                <span class="when">{{ $step['when']?->format('M d H:i') ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <h2>{{ __('Related Transactions') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="chain">
                        <span class="here">{{ $expense->expense_number }}</span>
                        @if($expense->reference)
                            <span class="arr">›</span>
                            <span class="link-like">INV-{{ $expense->reference }}</span>
                        @endif
                        @if($firstPayment)
                            <span class="arr">›</span>
                            <span class="link-like">PAY-{{ $firstPayment->payment_number }}</span>
                        @endif
                        @if($journalEntry)
                            <span class="arr">›</span>
                            <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}">{{ $journalEntry->journal_number }}</a>
                        @endif
                        <span class="arr">›</span>
                        <a href="{{ route('accounting.general-ledger.index') }}">{{ __('General Ledger') }}</a>
                    </div>
                </div>

                @if($expense->payments->count())
                    <div class="card-sec" id="payments">
                        <div class="sec-head">
                            <span class="sec-ic">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8"/></svg>
                            </span>
                            <h2>{{ __('Payments') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>{{ __('Payment #') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Method') }}</th>
                                        <th>{{ __('Account') }}</th>
                                        <th class="num">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expense->payments as $payment)
                                        <tr>
                                            <td><span class="mono">{{ $payment->payment_number }}</span></td>
                                            <td class="em">{{ $payment->payment_date?->format('M d, Y') }}</td>
                                            <td class="em">{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '')) }}</td>
                                            <td class="em">{{ $payment->account?->name ?? '—' }}</td>
                                            <td class="numr">{{ format_number($payment->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <h2>{{ __('Audit Trail') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="audit" id="audit">
                        @forelse($auditTrail as $log)
                            <div class="arow">
                                <span class="when">{{ $log->created_at?->format('M d H:i') ?? '—' }}</span>
                                <span class="who">{{ $log->user?->name ?? '—' }}</span>
                                <span class="what">
                                    {{ $auditLabels[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action)) }}
                                    @if($log->notes)
                                        <span class="note"> · {{ $log->notes }}</span>
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="arow"><span class="what">{{ __('No audit entries recorded.') }}</span></div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
