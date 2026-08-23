<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                <h1 class="rv-title">{{ __('Reversal Request') }} — {{ $requestModel->reference_number }}</h1>
                <span class="rv-badge rv-badge--{{ $requestModel->statusColor() }}">
                    <span class="rv-dot"></span>
                    {{ $requestModel->statusLabel() }}
                </span>
            </div>
            <p class="rv-sub">{{ __('Submitted by') }} {{ $requestModel->requester?->name }} {{ __('on') }} {{ $requestModel->request_date?->format('d M Y') }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.index') }}" class="rv-btn rv-btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Original Transaction Detail --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Original Transaction') }}</span>
                    <a href="{{ route('accounting.journal-entries.show', $requestModel->journal_entry_id) }}" class="rv-link">{{ __('View in Journal') }} →</a>
                </div>

                <div class="rv-detail">
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Journal Number') }}</span>
                        <span class="rv-detail-value rv-mono">{{ $requestModel->journalEntry?->journal_number ?? '—' }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Date') }}</span>
                        <span class="rv-detail-value">{{ $requestModel->journalEntry?->date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Amount') }}</span>
                        <span class="rv-detail-value rv-numr">{{ number_format($requestModel->journalEntry?->total_debit ?? 0, 2) }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Reversal Method') }}</span>
                        <span class="rv-detail-value">{{ $requestModel->reversal_method === 'full' ? __('Full Reversal') : __('Partial — ') . number_format($requestModel->partial_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Reversal Date') }}</span>
                        <span class="rv-detail-value">{{ $requestModel->reversal_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Source Module') }}</span>
                        <span class="rv-detail-value">{{ ucfirst(str_replace('_', ' ', $requestModel->original_transaction_type)) }}</span>
                    </div>
                </div>

                @if($requestModel->journalEntry?->lines)
                    <div class="rv-table-wrap" style="margin-top:1rem">
                        <table class="rv-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th class="rv-right">{{ __('Debit') }}</th>
                                    <th class="rv-right">{{ __('Credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requestModel->journalEntry->lines as $line)
                                    <tr>
                                        <td>
                                            <span class="rv-mono">{{ $line->account?->code }}</span>
                                            {{ $line->account?->name }}
                                        </td>
                                        <td class="rv-right rv-numr">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                                        <td class="rv-right rv-numr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:700;background:rgba(244,248,248,.55)">
                                    <td>{{ __('Total') }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($requestModel->journalEntry->total_debit, 2) }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($requestModel->journalEntry->total_credit, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Reversal Reason --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Reversal Reason') }}</span>
                </div>
                <p style="font-size:.875rem;line-height:1.6;color:var(--deep-3)">{{ $requestModel->reason }}</p>
            </div>

            {{-- Authorization Chain --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Authorization Chain') }}</span>
                </div>
                @if($requestModel->authorizationRequests->count() > 0)
                    <div class="rv-chain">
                        @foreach($requestModel->authorizationRequests as $auth)
                            <div class="rv-chain-item {{ in_array($auth->status, ['approved']) ? 'is-done' : '' }}">
                                <span class="rv-chain-level">Level {{ $auth->approval_level }}</span>
                                <span class="rv-chain-assignee">{{ $auth->assignee?->name ?? '—' }}</span>
                                <span class="rv-chain-status">
                                    <span class="rv-badge rv-badge--{{ $auth->status === 'approved' ? 'approved' : ($auth->status === 'rejected' ? 'rejected' : 'pending') }}">
                                        <span class="rv-dot"></span>
                                        {{ ucfirst($auth->status) }}
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rv-mono" style="padding:0 1.25rem">{{ __('No authorization steps configured.') }}</p>
                @endif
            </div>

            {{-- Approval History --}}
            @if($requestModel->approvalHistory->count() > 0)
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Approval History') }}</span>
                    </div>
                    <div class="rv-timeline">
                        @foreach($requestModel->approvalHistory->sortByDesc('date_time') as $history)
                            <div class="rv-timeline-item">
                                <span class="rv-timeline-dot {{ in_array($history->action, ['rejected']) ? 'rv-timeline-dot--red' : (in_array($history->action, ['approved', 'posted_to_gl']) ? 'rv-timeline-dot--green' : 'rv-timeline-dot--amber') }}"></span>
                                <div class="rv-timeline-body">
                                    <span class="rv-timeline-title">{{ \App\Models\ReversalApprovalHistory::actionLabel($history->action) }}</span>
                                    <span class="rv-timeline-sub">— {{ $history->performer?->name }}</span>
                                    @if($history->remarks)
                                        <div class="rv-timeline-sub" style="margin-top:.25rem">{{ $history->remarks }}</div>
                                    @endif
                                    <span class="rv-timeline-time">{{ $history->date_time?->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Rail --}}
        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.show', $requestModel->id) }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></span>
                        {{ __('View Request') }}
                    </a>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></span>
                        {{ __('Back to Dashboard') }}
                    </a>
                    <a href="{{ route('accounting.reversals.create') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                        {{ __('New Request') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
