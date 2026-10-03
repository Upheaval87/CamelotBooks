{{-- ── View transaction ── --}}
<div class="jmodal" x-show="open.view" x-cloak @click.self="open.view = false" role="dialog" aria-modal="true" aria-labelledby="tc-view-title">
    <div class="mbox">
        <div class="m-head">
            <span class="m-ic" x-html="iconFor(viewTx ? viewTx.type : '')">
                <svg viewBox="0 0 24 24"><path d="M4 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v16l-3.5-2-3.5 2-3.5-2L4 21V5Z"/></svg>
            </span>
            <div>
                <div class="m-title" id="tc-view-title" x-text="viewTx ? (viewTx.typeLabel || '{{ __('Journal Entry') }}') : ''">{{ __('Journal Entry') }}</div>
            </div>
            <span class="m-ref" x-text="viewTx ? viewTx.ref : ''"></span>
            <span class="pill m-pill" :class="viewTx && viewTx.status === 'reversed' ? 'rev' : (viewTx && (viewTx.awaitingAuthorization || viewTx.postable) ? 'fin' : (viewTx && viewTx.status === 'draft' ? 'draft' : 'posted'))">
                <i></i><span x-text="viewTx && viewTx.status === 'reversed' ? '{{ __('Reversed') }}' : (viewTx && viewTx.awaitingAuthorization ? '{{ __('Awaiting authorization') }}' : (viewTx && viewTx.postable ? '{{ __('Unposted') }}' : (viewTx && viewTx.status === 'draft' ? '{{ __('Draft') }}' : '{{ __('Posted') }}')))"></span>
            </span>
            <button type="button" class="m-close" @click="open.view = false" aria-label="{{ __('Close') }}">
                <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="m-meta" x-show="viewTx">
            <span class="mchip"><span class="k">{{ __('Date') }}</span> <b x-text="viewTx ? viewTx.date : ''"></b></span>
            <span class="mchip"><span class="k">{{ __('Posted by') }}</span> <b x-text="viewTx ? (viewTx.postedBy || '—') : ''"></b></span>
            <span class="mchip b"><span class="k">{{ __('Amount') }}</span> <b class="mono" x-text="viewTx ? fmt(viewTx.amount) : ''"></b></span>
        </div>

        <div class="m-body" x-show="viewTx">
            <p class="m-memo" x-show="viewTx && viewTx.desc" x-text="viewTx ? viewTx.desc : ''"></p>

            <table class="mtable">
                <thead>
                    <tr>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="r">{{ __('Debit') }}</th>
                        <th class="r">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, i) in (viewTx ? viewTx.lines : [])" :key="i">
                        <tr>
                            <td><b x-text="line.code"></b> <span x-text="line.name"></span></td>
                            <td x-text="line.memo || '—'"></td>
                            <td class="r mono" x-text="line.debit ? fmt(line.debit) : '—'"></td>
                            <td class="r mono" x-text="line.credit ? fmt(line.credit) : '—'"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="totals">
                        <td colspan="2">{{ __('Total') }}</td>
                        <td class="r mono" x-text="fmt(viewTx ? viewTx.amount : 0)"></td>
                        <td class="r mono" x-text="fmt(viewTx ? viewTx.amount : 0)"></td>
                    </tr>
                    <tr class="bal-row">
                        <td colspan="4">
                            <span class="bal-chip">
                                <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                {{ __('Balanced') }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="m-foot">
            <span class="mnote" x-show="viewTx && viewTx.status === 'reversed'">{{ __('This entry has a posted reversal against it.') }}</span>
            <span class="mnote" x-show="viewTx && viewTx.awaitingAuthorization">{{ __('A reversal is awaiting authorization.') }}</span>
            <span class="mnote" x-show="viewTx && viewTx.postable">{{ __('Lines are locked. Posting writes this entry to the ledger.') }}</span>
            <div class="m-act">
                <button type="button" class="btn btn-ghost btn-sm" @click="open.view = false">{{ __('Close') }}</button>
                @can('journal-entries.post')
                    <button type="button" class="btn btn-primary btn-sm" x-show="viewTx && viewTx.postable && canPost" @click="askPost(viewTx.id)">{{ __('Post') }}</button>
                @endcan
                @can('transaction-reversals.request')
                    <button type="button" class="btn btn-danger-o btn-sm" x-show="viewTx && viewTx.reversible" @click="open.view = false; openReverse(viewTx)">{{ __('Capture reversal') }}</button>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- ── Capture reversal ── --}}
<div class="jmodal revz" x-show="open.reverse" x-cloak @click.self="open.reverse = false" role="dialog" aria-modal="true" aria-labelledby="tc-rev-title">
    <div class="mbox narrow">
        <div class="m-head">
            <span class="m-ic">
                <svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3"/></svg>
            </span>
            <div>
                <div class="m-title" id="tc-rev-title">{{ __('Capture reversal') }}</div>
            </div>
            <span class="m-ref" x-text="revTx ? revTx.ref : ''"></span>
            <button type="button" class="m-close" @click="open.reverse = false" aria-label="{{ __('Close') }}">
                <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="m-body" x-show="revTx">
            <div class="mirr">
                <div class="mh">{{ __('Mirrored impact') }}</div>
                <template x-for="(line, i) in (revTx ? revTx.lines : [])" :key="i">
                    <div class="mrow">
                        <div><b x-text="line.code"></b> <span x-text="line.name"></span></div>
                        <span class="side" :class="line.credit ? 'dr' : 'cr'" x-text="line.credit ? 'DR' : 'CR'"></span>
                        <span class="amt" :class="line.credit ? 'dr' : 'cr'" x-text="fmt(line.credit ? line.credit : line.debit)"></span>
                    </div>
                </template>
            </div>

            <form id="tc-reverse-form" method="POST" :action="revTx ? urls.reverse.replace('__ID__', revTx.id) : ''">
                @csrf
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <input type="hidden" name="tab" value="reversal">

                <div class="f-grid">
                    <label class="fld">
                        <span>{{ __('Reversal date') }} <em>*</em></span>
                        <input type="date" name="reversal_date" class="in" x-model="revForm.reversal_date" :max="today" required>
                    </label>
                    <label class="fld">
                        <span>{{ __('New reference') }}</span>
                        <input type="text" class="in" value="" placeholder="{{ __('Assigned on confirm') }}" readonly>
                    </label>
                    <label class="fld full">
                        <span>{{ __('Reason') }} <em>*</em></span>
                        <textarea name="reason" class="in" rows="3" minlength="3" maxlength="1000" x-model="revForm.reason" required></textarea>
                    </label>
                </div>

                <p class="mnote" style="margin-top:14px">{{ __('This reversal will be submitted for authorization. It posts to the ledger only after approval.') }}</p>
            </form>
        </div>

        <div class="m-foot">
            <div class="m-act">
                <button type="button" class="btn btn-ghost btn-sm" @click="open.reverse = false">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-cta btn-sm" @click="askReverse()">{{ __('Submit for authorization') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Authorization review ── --}}
<div class="jmodal" x-show="open.auth" x-cloak @click.self="open.auth = false" role="dialog" aria-modal="true" aria-labelledby="tc-auth-title">
    <div class="mbox narrow">
        <div class="m-head">
            <span class="m-ic">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
            </span>
            <div>
                <div class="m-title" id="tc-auth-title">{{ __('Authorization review') }}</div>
            </div>
            <span class="m-ref" x-text="authReq ? authReq.ref : ''"></span>
            <span class="pill m-pill fin"><i></i>{{ __('Pending') }}</span>
            <button type="button" class="m-close" @click="open.auth = false" aria-label="{{ __('Close') }}">
                <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="m-meta" x-show="authReq">
            <span class="mchip"><span class="k">{{ __('Type') }}</span> <b x-text="authReq ? (authReq.typeLabel || '—') : ''"></b></span>
            <span class="mchip"><span class="k">{{ __('Target') }}</span> <b x-text="authReq && authReq.entry ? authReq.entry.ref : '—'"></b></span>
            <span class="mchip"><span class="k">{{ __('Requested by') }}</span> <b x-text="authReq ? (authReq.requester || '—') : ''"></b></span>
            <span class="mchip"><span class="k">{{ __('Submitted') }}</span> <b x-text="authReq ? (authReq.submitted || '—') : ''"></b></span>
            <span class="mchip b"><span class="k">{{ __('Amount') }}</span> <b class="mono" x-text="authReq ? fmt(authReq.amount) : ''"></b></span>
        </div>

        <div class="m-body" x-show="authReq">
            <p class="m-memo" x-show="authReq && authReq.reason" x-text="authReq ? authReq.reason : ''"></p>

            <div class="mirr" x-show="authReq && authReq.entry">
                <div class="mh">{{ __('Mirrored impact') }}</div>
                <template x-for="(line, i) in (authReq && authReq.entry ? authReq.entry.lines : [])" :key="i">
                    <div class="mrow">
                        <div><b x-text="line.code"></b> <span x-text="line.name"></span></div>
                        <span class="side" :class="line.credit ? 'dr' : 'cr'" x-text="line.credit ? 'DR' : 'CR'"></span>
                        <span class="amt" :class="line.credit ? 'dr' : 'cr'" x-text="fmt(line.credit ? line.credit : line.debit)"></span>
                    </div>
                </template>
            </div>

            <label class="fld">
                <span>{{ __('Comment') }}</span>
                <textarea class="in" rows="2" maxlength="1000" x-model="authComment" placeholder="{{ __('Optional note for the record') }}"></textarea>
            </label>
        </div>

        <div class="m-foot end">
            <span class="mnote" x-show="authReq && !authReq.canDecide">{{ __('Separation of duties: another approver must decide this request.') }}</span>
            <div class="m-act" style="margin-left:0" x-show="authReq && authReq.canDecide">
                @can('transaction-reversals.reject')
                    <button type="button" class="btn btn-danger-o btn-sm" @click="askReject()">{{ __('Reject') }}</button>
                @endcan
                @can('transaction-reversals.approve')
                    <button type="button" class="btn btn-green btn-sm" @click="askApprove()">{{ __('Authorize') }}</button>
                @endcan
            </div>
        </div>
    </div>
</div>

{{-- ── Notification (separation of duties) ── --}}
<div class="jmodal confirm" x-show="notice.open" x-cloak @click.self="notice.open = false" role="dialog" aria-modal="true" aria-labelledby="tc-notice-title">
    <div class="cbox">
        <div class="cb-head">
            <span class="cb-ic danger">
                <svg viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
            </span>
            <h3 id="tc-notice-title">{{ __('You cannot approve a reversal you initiated') }}</h3>
            <button type="button" class="cb-x" @click="notice.open = false" aria-label="{{ __('Close') }}">&times;</button>
        </div>
        <div class="cb-body">
            <p>{{ __('Separation of duties requires that a different person authorize a reversal you captured.') }}</p>
        </div>
        <div class="cb-foot">
            <button type="button" class="btn btn-cta btn-sm" @click="notice.open = false">{{ __('OK') }}</button>
        </div>
    </div>
</div>

{{-- ── Confirm box ── --}}
<div class="jmodal confirm" x-show="confirm.open" x-cloak @click.self="confirm.open = false" role="dialog" aria-modal="true" aria-labelledby="tc-confirm-title">
    <div class="cbox">
        <div class="cb-head">
            <span class="cb-ic" :class="confirm.tone === 'danger' ? 'danger' : ''">
                <svg viewBox="0 0 24 24" x-show="confirm.tone !== 'danger'"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                <svg viewBox="0 0 24 24" x-show="confirm.tone === 'danger'"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
            </span>
            <h3 id="tc-confirm-title" x-text="confirm.title"></h3>
            <button type="button" class="cb-x" @click="confirm.open = false" aria-label="{{ __('Close') }}">&times;</button>
        </div>
        <div class="cb-body">
            <p x-text="confirm.body"></p>
            <label class="fld" x-show="confirm.note" style="margin-top:12px">
                <span x-text="confirm.noteRequired ? '{{ __('Reason') }} *' : '{{ __('Note') }}'"></span>
                <textarea class="in" rows="3" x-model="confirm.noteValue" :placeholder="confirm.notePlaceholder"></textarea>
            </label>
        </div>
        <div class="cb-foot">
            <button type="button" class="btn btn-ghost btn-sm" @click="confirm.open = false">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-sm" :class="confirm.tone === 'danger' ? 'btn-danger' : 'btn-cta'" @click="confirmYes()" x-text="confirm.label"></button>
        </div>
    </div>
</div>

{{-- ── Select period ── --}}
<div class="jmodal" x-show="open.period" x-cloak @click.self="open.period = false" role="dialog" aria-modal="true" aria-labelledby="tc-period-title">
    <div class="mbox narrow">
        <div class="m-head">
            <span class="m-ic">
                <svg viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
            </span>
            <div>
                <div class="m-title" id="tc-period-title">{{ __('Select period') }}</div>
            </div>
            <button type="button" class="m-close" @click="open.period = false" aria-label="{{ __('Close') }}">
                <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="m-body">
            <form method="GET" action="{{ route('accounting.transaction-controls.index') }}" id="tc-period-form" data-tc-period-form>
                <input type="hidden" name="tab" value="reversal">

                <div class="f-grid">
                    <label class="fld">
                        <span>{{ __('From') }} <em>*</em></span>
                        <input type="date" name="from" class="in" value="{{ $from ?? now()->startOfMonth()->format('Y-m-d') }}" required>
                    </label>
                    <label class="fld">
                        <span>{{ __('To') }} <em>*</em></span>
                        <input type="date" name="to" class="in" value="{{ $to ?? now()->format('Y-m-d') }}" required>
                    </label>
                </div>

                <div class="presets">
                    <span>{{ __('Quick ranges') }}</span>
                    <button type="button" class="preset" :class="periodPreset === 'today' ? 'on' : ''" @click="setRange($event, 'today')">{{ __('Today') }}</button>
                    <button type="button" class="preset" :class="periodPreset === '7d' ? 'on' : ''" @click="setRange($event, '7d')">{{ __('Last 7 days') }}</button>
                    <button type="button" class="preset" :class="periodPreset === 'month' ? 'on' : ''" @click="setRange($event, 'month')">{{ __('This month') }}</button>
                    <button type="button" class="preset" :class="periodPreset === '30d' ? 'on' : ''" @click="setRange($event, '30d')">{{ __('Last 30 days') }}</button>
                </div>

                <div style="margin-top:18px">
                    <button type="submit" class="btn btn-cta">
                        <svg viewBox="0 0 24 24"><path d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>
                        {{ __('Load transactions') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="m-foot end">
            <div class="m-act">
                <button type="button" class="btn btn-ghost btn-sm" @click="open.period = false">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Hidden action forms ── --}}
<form id="tc-delete-form" method="POST" :action="unpTx ? urls.destroy.replace('__ID__', unpTx.id) : ''" class="hiddenform">
    @csrf
    @method('DELETE')
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
</form>

<form id="tc-reopen-form" method="POST" :action="unpTx ? urls.reopen.replace('__ID__', unpTx.id) : ''" class="hiddenform">
    @csrf
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
    <input type="hidden" name="reason" value="">
</form>

<form id="tc-post-form" method="POST" :action="unpTx ? urls.post.replace('__ID__', unpTx.id) : ''" class="hiddenform">
    @csrf
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
</form>

<form id="tc-approve-form" method="POST" :action="authReq ? urls.approve.replace('__ID__', authReq.id) : ''" class="hiddenform">
    @csrf
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
    <input type="hidden" name="comments" value="">
</form>

<form id="tc-reject-form" method="POST" :action="authReq ? urls.reject.replace('__ID__', authReq.id) : ''" class="hiddenform">
    @csrf
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
    <input type="hidden" name="reason" value="">
</form>
