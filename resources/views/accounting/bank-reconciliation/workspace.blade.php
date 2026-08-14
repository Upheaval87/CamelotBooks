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

                $buckets = [
                    'Exact' => $suggestions['exact'] ?? collect(),
                    'Likely' => $suggestions['likely'] ?? collect(),
                    'Possible' => $suggestions['possible'] ?? collect(),
                ];
                $hasSuggestions = collect($buckets)->contains(fn ($c) => $c->count() > 0);

                $unmatchedLines = $statementLines->whereNull('match_id')->count();
            @endphp

            <div class="br-head">
                <div>
                    <h1>
                        {{ __('Bank Reconciliation') }}
                        <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                    </h1>
                    <div class="sub">
                        {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}
                        @if($reconciliation->period_start && $reconciliation->period_end)
                            &middot; {{ $reconciliation->period_start->format('M d, Y') }} – {{ $reconciliation->period_end->format('M d, Y') }}
                        @elseif($reconciliation->statement_date)
                            &middot; Statement {{ $reconciliation->statement_date->format('M d, Y') }}
                        @endif
                    </div>
                </div>
                <div class="br-cluster">
                    @if(!$locked)
                        <a href="{{ route('accounting.bank-reconciliation.import', $reconciliation->id) }}" class="btn ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            {{ __('Import Statement') }}
                        </a>
                        <form method="POST" action="{{ route('accounting.bank-reconciliation.auto-match', $reconciliation->id) }}" style="display:inline-flex">
                            @csrf
                            <button type="submit" class="btn sec">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>
                                {{ __('Auto Match') }}
                            </button>
                        </form>
                        @if(in_array($reconciliation->status, [\App\Models\Reconciliation::STATUS_DRAFT, \App\Models\Reconciliation::STATUS_IN_PROGRESS], true))
                            <form method="POST" action="{{ route('accounting.bank-reconciliation.ready', $reconciliation->id) }}" style="display:inline-flex">
                                @csrf
                                <button type="submit" class="btn cta">{{ __('Mark Ready for Review') }}</button>
                            </form>
                        @elseif($reconciliation->status === \App\Models\Reconciliation::STATUS_READY_FOR_REVIEW)
                            <form method="POST" action="{{ route('accounting.bank-reconciliation.reopen', $reconciliation->id) }}" style="display:inline-flex">
                                @csrf
                                <button type="submit" class="btn ghost">{{ __('Reopen') }}</button>
                            </form>
                        @endif
                    @endif
                    <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        {{ __('Review & Finalize') }}
                    </a>
                    <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn ghost">
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

            @if($locked)
                <div class="note-info" style="margin-bottom:16px">
                    This reconciliation is {{ $statusBadge[1] }} and locked. Actions are read-only.
                </div>
            @endif

            <section class="card" style="margin-bottom:16px">
                <div class="card-h">
                    <h2>
                        {{ $reconciliation->bankAccount?->code }} {{ $reconciliation->bankAccount?->name }}
                        @if($reconciliation->statement_number) &middot; {{ $reconciliation->statement_number }} @endif
                    </h2>
                    <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                    <span class="rule"></span>
                    <span class="n">{{ $statementLines->count() }} {{ __('statement lines') }}</span>
                </div>
                <div class="card-b">
                    @if($hasSuggestions)
                        <div class="confbar">
                            ⚡ {{ __('Auto Match found') }} <b>{{ collect($suggestions['exact'] ?? [])->count() }} {{ __('exact') }}</b> {{ __('and') }} <b>{{ collect($suggestions['likely'] ?? [])->count() }} {{ __('likely') }}</b> {{ __('match.') }}
                            @if(collect($suggestions['exact'] ?? [])->count() > 0)
                                <span class="conf exact">Exact · 100%</span>
                            @elseif(collect($suggestions['likely'] ?? [])->count() > 0)
                                <span class="conf likely">Likely · ~94%</span>
                            @endif
                        </div>
                    @endif

                    <div class="panes">
                        <div class="card pane book" style="box-shadow:none">
                            <div class="pane-h">
                                <span class="t">{{ __('Book Transactions') }}</span>
                                <span class="n">accounting system · {{ $transactions->count() }}</span>
                                <div class="right">
                                    <div class="searchbox">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        <input placeholder="Search book…">
                                    </div>
                                </div>
                            </div>
                            <div class="li-wrap" style="margin-top:0;border-radius:0;border:none">
                                <table style="min-width:640px">
                                    <thead>
                                        <tr>
                                            <th style="width:12%">{{ __('Date') }}</th>
                                            <th style="width:14%">{{ __('Reference') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="num" style="width:14%">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th style="width:13%">{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $tx)
                                            <tr>
                                                <td class="em">{{ $tx->date?->format('M d, Y') ?? '—' }}</td>
                                                <td class="mono em">{{ $tx->reference ?? '—' }}</td>
                                                <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $tx->description }}">{{ $tx->description ?? '—' }}</td>
                                                <td class="numr @if((float) $tx->amount < 0) red @endif">{{ format_number($tx->amount) }}</td>
                                                <td>
                                                    @if($tx->is_reconciled)
                                                        <span class="mchip m-matched">Matched</span>
                                                    @else
                                                        <span class="mchip m-out">Outstanding</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5"><div class="empty">No unmatched book transactions for this bank account.</div></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card pane bank" style="box-shadow:none">
                            <div class="pane-h">
                                <span class="t">{{ __('Statement Lines') }}</span>
                                <span class="n">imported · {{ $statementLines->count() }}</span>
                                <div class="right">
                                    <div class="searchbox">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                        <input placeholder="Search bank…">
                                    </div>
                                </div>
                            </div>
                            <div class="li-wrap" style="margin-top:0;border-radius:0;border:none">
                                <table style="min-width:760px">
                                    <thead>
                                        <tr>
                                            <th style="width:12%">{{ __('Date') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th style="width:12%">{{ __('Reference') }}</th>
                                            <th class="num" style="width:12%">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th style="width:12%">{{ __('Status') }}</th>
                                            <th style="width:22%">{{ __('Match') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($statementLines as $line)
                                            <tr>
                                                <td class="em">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                                <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $line->description }}">{{ $line->description ?? '—' }}</td>
                                                <td class="mono em">{{ $line->reference ?? '—' }}</td>
                                                <td class="numr @if((float) $line->amount < 0) red @endif">{{ format_number($line->amount) }}</td>
                                                <td>
                                                    @if($line->is_matched)
                                                        <span class="mchip m-matched">Matched</span>
                                                    @elseif($line->status === \App\Models\BankStatementLine::STATUS_ADJUSTED)
                                                        <span class="mchip m-adj">Adjusted</span>
                                                    @else
                                                        <span class="mchip m-bank">Bank-only</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($line->match)
                                                        <span class="em" style="font-size:11px">{{ $line->match->bankTransaction?->reference ?? '#' . $line->match->bank_transaction_id }}</span>
                                                    @elseif(!$locked)
                                                        <form method="POST" action="{{ route('accounting.bank-reconciliation.match', $reconciliation->id) }}" style="display:inline-flex;gap:4px;width:100%;justify-content:flex-end">
                                                            @csrf
                                                            <input type="hidden" name="bank_statement_line_id" value="{{ $line->id }}" />
                                                            <select name="bank_transaction_id" class="ci" style="max-width:120px">
                                                                <option value="">Select…</option>
                                                                @foreach($transactions as $tx)
                                                                    <option value="{{ $tx->id }}">{{ $tx->date?->format('m/d') }} · {{ $tx->reference ?? '#' . $tx->id }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="btn ghost sm">{{ __('Match') }}</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6"><div class="empty">No statement lines yet. Import your bank statement to begin.</div></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="calcgrid">
                <div class="card">
                    <div class="card-h">
                        <h2>{{ __('Reconciliation Calculation') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="card-b calc">
                        <div class="row"><span>{{ __('Book balance') }}</span><b>{{ $cs }} {{ format_number($reconciliation->book_balance) }}</b></div>
                        <div class="row"><span>{{ __('Statement balance') }}</span><b>{{ $cs }} {{ format_number($reconciliation->statement_balance) }}</b></div>
                        <div class="row"><span>{{ __('Adjustments') }}</span><b>{{ $adjustments->count() }}</b></div>
                        <div class="row tot"><span>{{ __('Difference') }}</span><b>{{ $cs }} {{ format_number($reconciliation->difference) }}</b></div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="card">
                        <div class="card-h">
                            <h2>{{ __('Balance Summary') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="card-b">
                            <div class="calc">
                                <div class="row"><span>{{ __('Book Balance') }}</span><b>{{ format_number($reconciliation->book_balance) }}</b></div>
                                <div class="row"><span>{{ __('Statement Balance') }}</span><b>{{ format_number($reconciliation->statement_balance) }}</b></div>
                                <div class="row"><span>{{ __('Adjustments') }}</span><b>{{ $adjustments->count() }}</b></div>
                                <div class="row"><span>{{ __('Matched') }}</span><b>{{ $matches->count() }}</b></div>
                                <div class="row"><span>{{ __('Unmatched Lines') }}</span><b>{{ $unmatchedLines }}</b></div>
                            </div>
                            <div class="balanced {{ $reconciliation->isBalanced() ? 'ok' : 'warn' }}" style="margin-top:12px">
                                @if($reconciliation->isBalanced())
                                    ✓ {{ __('BALANCED — the statement agrees with the books.') }}
                                @else
                                    ⚠ {{ __('Out of balance') }} — {{ __('difference of') }} {{ $cs }} {{ format_number($reconciliation->difference) }} {{ __('remains.') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-h">
                            <h2>{{ __('Suggestions') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="card-b">
                            @forelse($buckets as $bucketLabel => $bucket)
                                @if($bucket->count() > 0)
                                    <div class="glabel" style="margin-top:12px">{{ $bucketLabel }}</div>
                                    @foreach($bucket as $suggestion)
                                        <div class="srow">
                                            <span class="l" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $suggestion->line->description }}">
                                                {{ $suggestion->line->description }}
                                            </span>
                                            <span class="conf {{ $bucketLabel === 'Exact' ? 'exact' : ($bucketLabel === 'Likely' ? 'likely' : '') }}">{{ $suggestion->label }}</span>
                                        </div>
                                        <div class="srow" style="border-bottom:none;font-size:11.5px">
                                            <span class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $suggestion->transaction->description }}">
                                                → {{ $suggestion->transaction->reference ?? '#' . $suggestion->transaction->id }} · {{ $suggestion->transaction->description }}
                                            </span>
                                            @if(!$locked)
                                                <form method="POST" action="{{ route('accounting.bank-reconciliation.match', $reconciliation->id) }}" style="display:inline-flex">
                                                    @csrf
                                                    <input type="hidden" name="bank_statement_line_id" value="{{ $suggestion->line->id }}" />
                                                    <input type="hidden" name="bank_transaction_id" value="{{ $suggestion->transaction->id }}" />
                                                    <button type="submit" class="btn sec sm">{{ __('Match') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            @empty
                            @endforelse
                            @if(!$hasSuggestions)
                                <div class="empty">No auto-match suggestions. Match statement lines manually above.</div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-h">
                            <h2>{{ __('Imports') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="card-b">
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
                                <div class="empty">No statements imported yet.</div>
                            @endforelse
                            @if(!$locked)
                                <div style="margin-top:10px">
                                    <a href="{{ route('accounting.bank-reconciliation.import', $reconciliation->id) }}" class="btn ghost sm" style="width:100%">{{ __('Import Statement') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <section class="card" style="margin-top:16px">
                <div class="card-h">
                    <h2>{{ __('Adjustments') }}</h2>
                    <span class="chip-t">{{ $adjustments->count() }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:18%">{{ __('Type') }}</th>
                                <th style="width:12%">{{ __('Side') }}</th>
                                <th class="num" style="width:11%">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:30%">{{ __('Description') }}</th>
                                <th style="width:14%">{{ __('Account') }}</th>
                                <th style="width:15%"></th>
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
                                        @if(!$locked)
                                            <form method="POST" action="{{ route('accounting.bank-reconciliation.adjustments.destroy', [$reconciliation->id, $adjustment->id]) }}" style="display:inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ibtn del" title="{{ __('Remove adjustment') }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><div class="empty">No adjustments. Use the form below when a statement line or book entry needs correcting.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!$locked)
                    <div class="card-b" style="padding-top:0">
                        <form method="POST" action="{{ route('accounting.bank-reconciliation.adjustments.store', $reconciliation->id) }}" class="g4" style="grid-template-columns:repeat(6,minmax(0,1fr))">
                            @csrf
                            <div class="field">
                                <label class="glabel">{{ __('Type') }}</label>
                                <select name="type" class="ci">
                                    @foreach(\App\Models\ReconciliationAdjustment::TYPES as $type)
                                        <option value="{{ $type }}">{{ \App\Models\ReconciliationAdjustment::typeLabel($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label class="glabel">{{ __('Sign') }}</label>
                                <select name="sign" class="ci">
                                    <option value="add">Add (+)</option>
                                    <option value="subtract">Subtract (−)</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="glabel">{{ __('Side') }}</label>
                                <select name="side" class="ci">
                                    <option value="book">Book</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="glabel">{{ __('Amount') }} ({{ $cs }})</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="ci" required />
                            </div>
                            <div class="field">
                                <label class="glabel">{{ __('Account') }}</label>
                                <select name="account_id" class="ci">
                                    <option value="">—</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field" style="display:flex;align-items:flex-end;gap:6px">
                                <input type="text" name="description" class="ci" placeholder="Description (optional)" />
                                <button type="submit" class="btn sec sm">{{ __('Add') }}</button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
