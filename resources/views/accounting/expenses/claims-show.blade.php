<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @php
        $badgeMap = [
            'draft' => 'b-draft', 'pending' => 'b-pend', 'approved' => 'b-app',
            'rejected' => 'b-rej', 'reimbursed' => 'b-paid',
        ];
        $auditLabels = [
            'created' => __('Claim created'),
            'updated' => __('Claim updated'),
            'submitted_for_approval' => __('Submitted for approval'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            'reimbursed' => __('Reimbursed'),
        ];
    @endphp

    <div class="ex-suite wrap">
        <div class="sticky-head">
            <div style="display:flex;align-items:center;gap:12px">
                <a href="{{ route('accounting.expenses.claims.index') }}" class="icon-btn" aria-label="{{ __('Back to claims') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <span class="crumbs">{{ __('Expense Claims') }} <em>›</em> <strong>{{ $claim->claim_number }}</strong></span>
                <span class="badge {{ $badgeMap[$claim->status] ?? 'b-draft' }}" style="margin-left:4px"><span class="bdot"></span>{{ $claim->statusLabel() }}</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if($claim->isDraft())
                    @can('expense-claims.submit')
                        <form method="POST" action="{{ route('accounting.expenses.claims.submit', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Submit this claim for approval?') }}', { type: 'action' })">
                            @csrf
                            <button class="btn btn-cta btn-sm" type="submit">{{ __('Submit Claim') }}</button>
                        </form>
                    @endcan
                    @can('expense-claims.delete')
                        <form method="POST" action="{{ route('accounting.expenses.claims.destroy', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this claim?') }}', { type: 'danger' })">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm" type="submit">{{ __('Delete') }}</button>
                        </form>
                    @endcan
                @elseif($claim->isPending())
                    @can('expense-claims.approve')
                        <form method="POST" action="{{ route('accounting.expenses.claims.approve', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Approve this claim?') }}', { type: 'action' })">
                            @csrf
                            <button class="btn btn-cta btn-sm" type="submit">{{ __('Approve') }}</button>
                        </form>
                    @endcan
                    @can('expense-claims.reject')
                        <form method="POST" action="{{ route('accounting.expenses.claims.reject', $claim) }}" onsubmit="return fbPromptForm(event, '{{ __('Reason for rejecting this claim:') }}', { confirmLabel: '{{ __('Reject') }}', type: 'danger' })">
                            @csrf
                            <input type="hidden" name="reason" value="" />
                            <button class="btn btn-ghost btn-sm" type="submit">{{ __('Reject') }}</button>
                        </form>
                    @endcan
                @elseif($claim->isApproved())
                    @if($claim->expense)
                        <a class="btn btn-sec btn-sm" href="{{ route('accounting.expenses.show', $claim->expense) }}">{{ __('View Expense') }}</a>
                    @endif
                    @can('expense-claims.reimburse')
                        <form method="POST" action="{{ route('accounting.expenses.claims.reimburse', $claim) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Mark this claim as reimbursed?') }}', { type: 'action' })">
                            @csrf
                            <button class="btn btn-cta btn-sm" type="submit">{{ __('Reimburse') }}</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        @if($claim->rejection_reason)
            <div class="note-warn" style="margin-bottom:16px" role="alert">
                <strong>{{ __('Rejection reason') }}:</strong> {{ $claim->rejection_reason }}
            </div>
        @endif

        <div class="shell">
            <div class="main" style="display:flex;flex-direction:column;gap:16px">
                <section class="card">
                    <div class="card-h">
                        <h2>{{ __('Claim Information') }}</h2>
                        <span class="fmt" style="margin-left:auto">{{ $claim->statusLabel() }}</span>
                    </div>
                    <div class="card-sec">
                        <div class="g4">
                            <div class="field"><label>{{ __('Claim #') }}</label><div class="val mono">{{ $claim->claim_number }}</div></div>
                            <div class="field"><label>{{ __('Employee') }}</label><div class="val">{{ $claim->employee?->full_name ?? '—' }}</div></div>
                            <div class="field"><label>{{ __('Expense Date') }}</label><div class="val">{{ $claim->expense_date?->format('M d, Y') }}</div></div>
                            <div class="field"><label>{{ __('Amount') }} ({{ $cs }})</label><div class="val numr">{{ format_number($claim->amount) }}</div></div>
                            <div class="field"><label>{{ __('Category') }}</label><div class="val">{{ $claim->category?->name ?? '—' }}</div></div>
                            <div class="field"><label>{{ __('Branch') }}</label><div class="val">{{ $claim->branch?->name ?? '—' }}</div></div>
                            <div class="field"><label>{{ __('Cost Centre') }}</label><div class="val">{{ $claim->costCenter?->name ?? '—' }}</div></div>
                            <div class="field"><label>{{ __('Currency') }}</label><div class="val">{{ $claim->currency ?? '—' }}</div></div>
                            <div class="field"><label>{{ __('Payment Method') }}</label><div class="val">{{ ucwords(str_replace('_', ' ', $claim->payment_method ?? '—')) }}</div></div>
                            <div class="field"><label>{{ __('Reimburse To') }}</label><div class="val">{{ $claim->reimburse_to ?? '—' }}</div></div>
                        </div>
                        <div class="g4" style="margin-top:16px">
                            <div class="field" style="grid-column:1/-1"><label>{{ __('Description') }}</label><div class="val">{{ $claim->description ?? '—' }}</div></div>
                            <div class="field" style="grid-column:1/-1"><label>{{ __('Memo') }}</label><div class="val">{{ $claim->memo ?? '—' }}</div></div>
                        </div>
                    </div>
                </section>

                @if($claim->attachments->isNotEmpty())
                    <section class="card">
                        <div class="card-h">
                            <h2>{{ __('Receipts & Attachments') }}</h2>
                            <span class="fmt" style="margin-left:auto">{{ $claim->attachments->count() }}</span>
                        </div>
                        <div class="card-sec">
                            <div class="attchips">
                                @foreach($claim->attachments as $attachment)
                                    <a class="att" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank">📄 {{ $attachment->original_name }}</a>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                <section class="card">
                    <div class="card-h">
                        <h2>{{ __('Approval Timeline') }}</h2>
                    </div>
                    <div class="card-sec">
                        @if($auditTrail->isNotEmpty())
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:22%">{{ __('When') }}</th>
                                        <th style="width:18%">{{ __('Who') }}</th>
                                        <th>{{ __('What') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($auditTrail as $log)
                                        <tr>
                                            <td class="em">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                                            <td class="em">{{ $log->user_id ? (\App\Models\User::find($log->user_id)?->name ?? 'System') : 'System' }}</td>
                                            <td class="em">{{ $auditLabels[$log->action] ?? ucwords(str_replace('_', ' ', $log->action)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="em">{{ __('No activity recorded yet.') }}</div>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="rail">
                <div class="railcard">
                    <div class="rail-sec">
                        <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                        <div class="vlist">
                            <a class="vitem" href="{{ route('accounting.expenses.claims.index') }}"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('My Claims') }}</a>
                            <a class="vitem" href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Pending Approval') }}</a>
                            <a class="vitem" href="{{ route('accounting.expenses.dashboard') }}"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="2"/></svg></span>{{ __('Expense Dashboard') }}</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
