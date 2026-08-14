<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            @php
                $locked = $reconciliation->isLocked();
                $statusBadge = [
                    \App\Models\Reconciliation::STATUS_DRAFT => ['b-gray', 'Draft'],
                    \App\Models\Reconciliation::STATUS_IN_PROGRESS => ['b-draft', 'In Progress'],
                    \App\Models\Reconciliation::STATUS_READY_FOR_REVIEW => ['b-teal', 'Ready for Review'],
                    \App\Models\Reconciliation::STATUS_APPROVED => ['b-mint', 'Approved'],
                    \App\Models\Reconciliation::STATUS_RECONCILED => ['b-post', 'Reconciled'],
                    \App\Models\Reconciliation::STATUS_REVERSED => ['b-red', 'Reversed'],
                ][$reconciliation->status] ?? ['b-gray', \App\Models\Reconciliation::statusLabel($reconciliation->status)];
                $createdByMe = $reconciliation->created_by === auth()->id();
            @endphp

            <div class="sticky-head">
                <div>
                    <h1>
                        {{ __('Reconciliation Review') }}
                        <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                    </h1>
                    <div class="sub">
                        {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}
                        @if($reconciliation->period_start && $reconciliation->period_end)
                            &middot; {{ $reconciliation->period_start->format('M d, Y') }} – {{ $reconciliation->period_end->format('M d, Y') }}
                        @endif
                    </div>
                </div>
                <div class="tbtns">
                    @if(!$locked)
                        @if(in_array($reconciliation->status, [\App\Models\Reconciliation::STATUS_DRAFT, \App\Models\Reconciliation::STATUS_IN_PROGRESS], true))
                            <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="btn ghost">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                {{ __('Continue Working') }}
                            </a>
                        @elseif($reconciliation->status === \App\Models\Reconciliation::STATUS_READY_FOR_REVIEW && $approvalRequired && !$createdByMe)
                            <form method="POST" action="{{ route('accounting.bank-reconciliation.approve', $reconciliation->id) }}" style="display:inline-flex">
                                @csrf
                                <button type="submit" class="btn sec">{{ __('Approve') }}</button>
                            </form>
                        @endif
                        @if($reconciliation->status === \App\Models\Reconciliation::STATUS_APPROVED && !$createdByMe)
                            <form method="POST" action="{{ route('accounting.bank-reconciliation.complete', $reconciliation->id) }}" style="display:inline-flex">
                                @csrf
                                <button type="submit" class="btn cta">{{ __('Complete & Clear') }}</button>
                            </form>
                        @endif
                        @if($reconciliation->status === \App\Models\Reconciliation::STATUS_READY_FOR_REVIEW)
                            <form method="POST" action="{{ route('accounting.bank-reconciliation.reopen', $reconciliation->id) }}" style="display:inline-flex">
                                @csrf
                                <button type="submit" class="btn ghost">{{ __('Reopen') }}</button>
                            </form>
                        @endif
                    @endif
                    <a href="{{ route('accounting.bank-reconciliation.audit', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('Audit Trail') }}
                    </a>
                    <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back') }}
                    </a>
                </div>
            </div>

            @if($errors->any())
                <div class="note-info" style="margin-bottom:16px">
                    <strong>{{ __('Something went wrong') }}:</strong>
                    <ul style="margin:6px 0 0 18px;list-style:disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="sgrid">
                <div class="sbox">
                    <div class="l">{{ __('Book Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($reconciliation->book_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Statement Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($reconciliation->statement_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Difference') }} ({{ $cs }})</div>
                    <div class="v @if((float) $reconciliation->difference < 0) red @else mint @endif">{{ format_number($reconciliation->difference) }}</div>
                </div>
            </div>

            <div class="shell" style="margin-top:16px">
                <div style="min-width:0">
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                            <h2>{{ __('Reconciliation Details') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="g4" style="margin-top:14px">
                            <div class="ro">
                                <div class="l">{{ __('Bank Account') }}</div>
                                <div class="v">{{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Statement Number') }}</div>
                                <div class="v">{{ $reconciliation->statement_number ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Statement Date') }}</div>
                                <div class="v">{{ $reconciliation->statement_date?->format('M d, Y') ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Period') }}</div>
                                <div class="v">{{ $reconciliation->period_start?->format('M d, Y') ?? '—' }} – {{ $reconciliation->period_end?->format('M d, Y') ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Currency') }}</div>
                                <div class="v">{{ $reconciliation->currency ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Created By') }}</div>
                                <div class="v">{{ $reconciliation->createdBy?->name ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Approved By') }}</div>
                                <div class="v">{{ $reconciliation->approvedBy?->name ?? '—' }}</div>
                            </div>
                            <div class="ro">
                                <div class="l">{{ __('Completed By') }}</div>
                                <div class="v">{{ $reconciliation->completedBy?->name ?? '—' }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="card card-sec" style="margin-top:16px">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></span>
                            <h2>{{ __('Statement Lines') }}</h2>
                            <span class="chip-t">{{ $statementLines->count() }} {{ __('lines') }}</span>
                            <span class="rule"></span>
                        </div>
                        <div class="li-wrap" style="margin-top:0">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:11%">{{ __('Date') }}</th>
                                        <th style="width:30%">{{ __('Description') }}</th>
                                        <th style="width:12%">{{ __('Reference') }}</th>
                                        <th class="num" style="width:11%">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th class="num" style="width:11%">{{ __('Balance') }} ({{ $cs }})</th>
                                        <th style="width:11%">{{ __('Status') }}</th>
                                        <th style="width:14%">{{ __('Matched To') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($statementLines as $line)
                                        <tr>
                                            <td class="em">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $line->description }}">{{ $line->description ?? '—' }}</td>
                                            <td class="mono em">{{ $line->reference ?? '—' }}</td>
                                            <td class="numr @if((float) $line->amount < 0) red @endif">{{ format_number($line->amount) }}</td>
                                            <td class="numr">{{ format_number($line->balance) }}</td>
                                            <td class="num">
                                                @if($line->is_matched)
                                                    <span class="badge b-post"><span class="bdot"></span>Matched</span>
                                                @elseif($line->status === \App\Models\BankStatementLine::STATUS_ADJUSTED)
                                                    <span class="badge b-teal"><span class="bdot"></span>Adjusted</span>
                                                @else
                                                    <span class="badge b-gray"><span class="bdot"></span>Unmatched</span>
                                                @endif
                                            </td>
                                            <td class="em">{{ $line->match?->bankTransaction?->reference ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"><div class="empty">No statement lines.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="card card-sec" style="margin-top:16px">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                            <h2>{{ __('Adjustments') }}</h2>
                            <span class="chip-t">{{ $adjustments->count() }}</span>
                            <span class="rule"></span>
                        </div>
                        <div class="li-wrap" style="margin-top:0">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:18%">{{ __('Type') }}</th>
                                        <th style="width:10%">{{ __('Side') }}</th>
                                        <th class="num" style="width:12%">{{ __('Amount') }} ({{ $cs }})</th>
                                        <th style="width:32%">{{ __('Description') }}</th>
                                        <th style="width:16%">{{ __('Account') }}</th>
                                        <th style="width:12%">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($adjustments as $adjustment)
                                        <tr>
                                            <td>{{ \App\Models\ReconciliationAdjustment::typeLabel($adjustment->type) }}</td>
                                            <td class="em">{{ ucfirst($adjustment->side) }}</td>
                                            <td class="numr @if($adjustment->sign === 'subtract') red @endif">{{ format_number($adjustment->amount) }}</td>
                                            <td class="em">{{ $adjustment->description ?? '—' }}</td>
                                            <td class="em">{{ $adjustment->account?->code }} {{ $adjustment->account?->name }}</td>
                                            <td class="num">
                                                @if($adjustment->status === \App\Models\ReconciliationAdjustment::STATUS_POSTED)
                                                    <span class="badge b-post"><span class="bdot"></span>Posted</span>
                                                @elseif($adjustment->status === \App\Models\ReconciliationAdjustment::STATUS_PENDING)
                                                    <span class="badge b-draft"><span class="bdot"></span>Pending</span>
                                                @else
                                                    <span class="badge b-gray"><span class="bdot"></span>{{ ucfirst($adjustment->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6"><div class="empty">No adjustments.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="railsum">
                    <div class="card rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                            <h2>{{ __('Balance Summary') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="srow"><span class="l">{{ __('Book Balance') }}</span><span class="v">{{ format_number($reconciliation->book_balance) }}</span></div>
                        <div class="srow"><span class="l">{{ __('Statement Balance') }}</span><span class="v">{{ format_number($reconciliation->statement_balance) }}</span></div>
                        <div class="srow strong"><span class="l">{{ __('Difference') }}</span><span class="v">{{ format_number($reconciliation->difference) }}</span></div>
                        <div class="srow"><span class="l">{{ __('Adjustments') }}</span><span class="v">{{ $adjustments->count() }}</span></div>
                        <div class="srow"><span class="l">{{ __('Matched') }}</span><span class="v">{{ $matches->count() }}</span></div>
                        <div class="gt">
                            <span class="l">{{ __('Result') }}</span>
                            <span class="v">
                                @if($reconciliation->isBalanced())
                                    Balanced
                                @else
                                    Out of balance
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="card rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg></span>
                            <h2>{{ __('Imports') }}</h2>
                            <span class="rule"></span>
                        </div>
                        @forelse($imports as $import)
                            <div class="srow">
                                <span class="l" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $import->filename }}">{{ $import->filename }}</span>
                                <span class="v">{{ $import->line_count }} {{ __('lines') }}</span>
                            </div>
                            <div class="srow" style="border-bottom:none;font-size:11.5px">
                                <span class="em">{{ $import->created_at?->format('M d, Y g:i A') ?? '—' }}</span>
                                <span class="em">{{ $import->importedBy?->name ?? '—' }}</span>
                            </div>
                        @empty
                            <div class="empty">No statements imported.</div>
                        @endforelse
                    </div>

                    <div class="card rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            <h2>{{ __('Actions') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.bank-reconciliation.audit', $reconciliation->id) }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                                {{ __('Audit Trail') }}
                            </a>
                            <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></span>
                                {{ __('Back to Workspace') }}
                            </a>
                            <a href="{{ route('accounting.bank-reconciliation.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                                {{ __('Reconciliation Register') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$locked && in_array($reconciliation->status, [\App\Models\Reconciliation::STATUS_READY_FOR_REVIEW, \App\Models\Reconciliation::STATUS_APPROVED, \App\Models\Reconciliation::STATUS_RECONCILED], true) && !$createdByMe)
                <div class="card card-sec" style="margin-top:16px">
                    <div class="sec-head">
                        <span class="sec-ic" style="background:var(--red, #DC2626)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg></span>
                        <h2>{{ __('Reverse') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <p class="sub" style="margin-top:8px">Reversing unlocks the reconciliation and restores its book transactions to an unreconciled state. A reason is required and recorded permanently in the audit trail.</p>
                    <form method="POST" action="{{ route('accounting.bank-reconciliation.reverse', $reconciliation->id) }}" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start">
                        @csrf
                        <input type="text" name="reason" class="ci" style="max-width:420px" placeholder="Reason for reversal" required />
                        <button type="submit" class="btn danger-o">{{ __('Reverse Reconciliation') }}</button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
