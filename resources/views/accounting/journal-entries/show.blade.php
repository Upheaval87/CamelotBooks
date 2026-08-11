<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $statusMap = [
            'draft' => ['draft', __('Draft')],
            'pending_approval' => ['teal', __('Pending Approval')],
            'posted' => ['post', __('Posted')],
            'reversed' => ['void', __('Reversed')],
            'rejected' => ['red', __('Rejected')],
        ];
        [$statusCls, $statusLabel] = $statusMap[$journalEntry->status] ?? ['gray', ucfirst(str_replace('_', ' ', $journalEntry->status))];
        $totalDebit = (float) $journalEntry->total_debit;
        $totalCredit = (float) $journalEntry->total_credit;
        $balance = $totalDebit - $totalCredit;
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Journal Entry') }} <span class="mono-chip">{{ $journalEntry->journal_number }}</span></h1>
                    <div class="sub">{{ $journalEntry->date->format('M d, Y') }}
                        @if ($journalEntry->reference) · {{ $journalEntry->reference }} @endif
                        @if ($journalEntry->branch) · {{ $journalEntry->branch->name }} @endif
                        · {{ __('created by') }} {{ $journalEntry->createdBy->name ?? '—' }}
                    </div>
                </div>
                <div class="tbtns">
                    @if($journalEntry->status === 'draft')
                        @can('journal-entries.submit')
                            <form method="POST" action="{{ route('accounting.journal-entries.submit-for-approval', $journalEntry) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Submit this entry for approval?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="btn btn-sec">{{ __('Submit for Approval') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if($journalEntry->status === 'pending_approval')
                        @can('journal-entries.approve')
                            <form method="POST" action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Approve and post this entry?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="btn btn-cta">{{ __('Approve & Post') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if($journalEntry->status === 'posted')
                        @can('journal-entries.reverse')
                            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Reverse this entry?', { type: 'danger' })">
                                @csrf
                                <button type="submit" class="btn btn-danger">{{ __('Reverse') }}</button>
                            </form>
                        @endcan
                    @endif
                    <button type="button" onclick="window.print()" class="btn btn-ghost">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- profile header --}}
                    <section class="card">
                        <div class="prof">
                            <span class="ava-xl"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div>
                                <div class="n">{{ __('Journal Entry') }} {{ $journalEntry->journal_number }} <span class="badge b-{{ $statusCls }}"><span class="bdot"></span>{{ $statusLabel }}</span></div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ __('Date') }} · {{ $journalEntry->date->format('M d, Y') }}</span>
                                    @if($journalEntry->reference)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ $journalEntry->reference }}</span>
                                    @endif
                                    @if($journalEntry->branch)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $journalEntry->branch->name }}</span>
                                    @endif
                                    @if($journalEntry->is_adjusting_entry)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v3m0 12v3M3 12h3m12 0h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ __('Adjusting Entry') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="acts">
                                <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-sec btn-sm">{{ __('New') }}</a>
                            </div>
                        </div>
                    </section>

                    {{-- stat grid --}}
                    <div class="statgrid">
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 5l-7 7M12 12H7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/></svg></span>
                            <div><div class="l">{{ __('Total Debit') }} ({{ $cs }})</div><div class="v">{{ format_number($totalDebit) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M19 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Total Credit') }} ({{ $cs }})</div><div class="v">{{ format_number($totalCredit) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v3m0 12v3M3 12h3m12 0h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Balance') }} ({{ $cs }})</div><div class="v @if(abs($balance) > 0.005) red @else mint @endif">{{ format_number($balance) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Lines') }}</div><div class="v">{{ count($journalEntry->lines) }}</div></div></div>
                    </div>

                    @if($journalEntry->status === 'pending_approval')
                        {{-- decision card --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></span>
                                <h2>{{ __('Review & Decide') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g4">
                                <div class="field sp3">
                                    <label for="rejection_reason">{{ __('Reason for Rejection') }}</label>
                                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="input" form="je-reject-form" style="min-height:100px" required></textarea>
                                    <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                                </div>
                            </div>
                            <div class="tbtns" style="margin-top:16px">
                                <button type="submit" form="je-reject-form" class="btn btn-danger-o" onclick="return fbConfirmButton(event, 'Reject this entry? The reason will be recorded.', { type: 'danger' })">{{ __('Reject') }}</button>
                                <form id="je-approve-form" method="POST" action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Approve and post this entry?', { type: 'action' })">
                                    @csrf
                                    <button type="submit" class="btn btn-cta">{{ __('Approve & Post') }}</button>
                                </form>
                            </div>
                        </section>
                    @elseif($journalEntry->status === 'posted')
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                <h2>{{ __('Posted') }}</h2>
                                <span class="rule"></span>
                                <span class="badge b-post"><span class="bdot"></span>{{ __('POSTED') }}</span>
                            </div>
                            <p class="sub" style="margin:10px 0 0">{{ __('Journal entry approved and posted.') }}
                                {{ __('Approved by') }} {{ $journalEntry->approvedByUser->name ?? $journalEntry->postedByUser->name ?? '—' }}
                                @if($journalEntry->approved_at) · {{ \Illuminate\Support\Carbon::parse($journalEntry->approved_at)->format('M d, Y h:i A') }} @endif
                            </p>
                        </section>
                    @elseif($journalEntry->status === 'rejected')
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                <h2>{{ __('Rejected') }}</h2>
                                <span class="rule"></span>
                                <span class="badge b-red"><span class="bdot"></span>{{ __('REJECTED') }}</span>
                            </div>
                            <p class="sub" style="margin:10px 0 0">{{ $journalEntry->rejection_reason ?: __('The entry was not approved.') }}</p>
                        </section>
                    @elseif($journalEntry->status === 'reversed')
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v6h6M20 20v-6h-6M5.5 10a7 7 0 0 1 12-3.5L20 9M18.5 14a7 7 0 0 1-12 3.5L4 15" /></svg></span>
                                <h2>{{ __('Reversed') }}</h2>
                                <span class="rule"></span>
                                <span class="badge b-void"><span class="bdot"></span>{{ __('REVERSED') }}</span>
                            </div>
                            <p class="sub" style="margin:10px 0 0">{{ __('This entry was reversed and no longer affects the ledger.') }}</p>
                        </section>
                    @endif

                    {{-- tabs --}}
                    <section class="card">
                        <div class="card-sec" style="padding-bottom:8px">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Contents') }}</h2><span class="rule"></span></div>
                            <div class="tabs" role="tablist">
                                <button type="button" class="tab on" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                                <button type="button" class="tab" data-target="tab-lines" role="tab">{{ __('Journal Lines') }}</button>
                                @if($journalEntry->auditLogs->count() > 0)
                                    <button type="button" class="tab" data-target="tab-audit" role="tab">{{ __('Audit Trail') }}</button>
                                @endif
                            </div>

                            <div class="tab-panel" id="tab-overview">
                                <div class="g4">
                                    <div class="field"><label>{{ __('Journal Number') }}</label><span class="val mono">{{ $journalEntry->journal_number }}</span></div>
                                    <div class="field"><label>{{ __('Date') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $journalEntry->date->format('M d, Y') }}</span></div>
                                    <div class="field"><label>{{ __('Reference') }}</label><span class="val mono">{{ $journalEntry->reference ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Branch') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $journalEntry->branch->name ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Adjusting Entry') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $journalEntry->is_adjusting_entry ? __('Yes') : __('No') }}</span></div>
                                    <div class="field"><label>{{ __('Created By') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $journalEntry->createdBy->name ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Posted By') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $journalEntry->postedByUser->name ?? '—' }}</span></div>
                                    @if($journalEntry->posted_at)
                                        <div class="field"><label>{{ __('Posted At') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{\Illuminate\Support\Carbon::parse($journalEntry->posted_at)->format('M d, Y h:i A') }}</span></div>
                                    @endif
                                    @if($journalEntry->rejection_reason)
                                        <div class="field sp2"><label>{{ __('Rejection Reason') }}</label><span style="font-weight:600;color:var(--red,#DC2626)">{{ $journalEntry->rejection_reason }}</span></div>
                                    @endif
                                    <div class="field sp3"><label>{{ __('Description') }}</label><span class="em" style="font-size:.8125rem">{{ $journalEntry->memo ?? '—' }}</span></div>
                                </div>
                            </div>

                            <div class="tab-panel" id="tab-lines" style="display:none">
                                <div class="li-wrap">
                                    <table>
                                        <thead><tr>
                                            <th style="width:4%">#</th>
                                            <th style="width:26%">{{ __('Account') }}</th>
                                            <th class="num" style="width:14%">{{ __('Debit') }} ({{ $cs }})</th>
                                            <th class="num" style="width:14%">{{ __('Credit') }} ({{ $cs }})</th>
                                            <th style="width:24%">{{ __('Description') }}</th>
                                            <th style="width:18%">{{ __('Branch') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($journalEntry->lines as $index => $line)
                                                <tr>
                                                    <td style="font-weight:600;color:var(--muted,#5F7476)">{{ $index + 1 }}</td>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)"><span class="mono">{{ $line->account->code }}</span> {{ $line->account->name }}</td>
                                                    <td class="numr">{{ $line->debit > 0 ? format_number($line->debit) : '' }}</td>
                                                    <td class="numr">{{ $line->credit > 0 ? format_number($line->credit) : '' }}</td>
                                                    <td class="em">{{ $line->memo ?? '' }}</td>
                                                    <td class="em">{{ $line->branch->name ?? $journalEntry->branch->name ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="li-totals" style="margin-top:12px"><div class="box">
                                    <div class="trow"><span>{{ __('Total Debit') }}</span><span class="v">{{ format_number($totalDebit) }}</span></div>
                                    <div class="trow"><span>{{ __('Total Credit') }}</span><span class="v">{{ format_number($totalCredit) }}</span></div>
                                    <div class="trow total"><span>{{ __('Balance') }}</span><span class="v">{{ format_number($balance) }}</span></div>
                                </div></div>
                            </div>

                            @if($journalEntry->auditLogs->count() > 0)
                                <div class="tab-panel" id="tab-audit" style="display:none">
                                    <div class="li-wrap">
                                        <table>
                                            <thead><tr>
                                                <th style="width:18%">{{ __('Date') }}</th>
                                                <th style="width:16%">{{ __('Action') }}</th>
                                                <th style="width:14%">{{ __('User') }}</th>
                                                <th>{{ __('Details') }}</th>
                                            </tr></thead>
                                            <tbody>
                                                @foreach($journalEntry->auditLogs->sortByDesc('created_at') as $log)
                                                    <tr>
                                                        <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                                        <td><span class="em" style="text-transform:capitalize">{{ str_replace('_', ' ', $log->action) }}</span></td>
                                                        <td class="em">{{ $log->user->name ?? '—' }}</td>
                                                        <td class="em" style="font-size:.8rem">
                                                            @if($log->old_values)
                                                                <span style="color:var(--red,#DC2626)">{{ __('Old') }}:</span> {{ json_encode($log->old_values) }}
                                                            @endif
                                                            @if($log->new_values)
                                                                <span style="color:#15803d;margin-left:8px">{{ __('New') }}:</span> {{ json_encode($log->new_values) }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                            <div style="margin-top:8px">
                                <div class="srow"><span class="l">{{ __('Total Debit') }}</span><span class="v">{{ format_number($totalDebit) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Total Credit') }}</span><span class="v">{{ format_number($totalCredit) }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Balance') }}</span><span class="v">{{ format_number($balance) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Status') }}</span><span class="v">{{ strtoupper(str_replace('_', ' ', $journalEntry->status)) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <button type="button" onclick="window.print()" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Print') }}</button>
                                <a href="{{ route('accounting.journal-entries.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('New Journal Entry') }}</a>
                                <a href="{{ route('accounting.accounts.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Lookup Account') }}</a>
                                <a href="{{ route('accounting.journal-entries.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('All Journal Entries') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <form id="je-reject-form" method="POST" action="{{ route('accounting.journal-entries.reject', $journalEntry) }}">
        @csrf
    </form>

    <script>
        document.querySelectorAll('.suite .tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.suite .tab').forEach(t => t.classList.remove('on'));
                tab.classList.add('on');
                document.querySelectorAll('.suite .tab-panel').forEach(p => {
                    p.style.display = (p.id === tab.dataset.target) ? '' : 'none';
                });
            });
        });
    </script>
</x-app-layout>
