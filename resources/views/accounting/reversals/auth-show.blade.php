<x-app-layout>
@php
    $req = $authorization->request;
@endphp
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                <h1 class="rv-title">{{ __('Review & Authorize') }} — {{ $req?->reference_number }}</h1>
                <span class="rv-badge rv-badge--{{ $req?->statusColor() ?? 'pending' }}">
                    <span class="rv-dot"></span>
                    {{ $req?->statusLabel() ?? ucfirst($authorization->status) }}
                </span>
            </div>
            <p class="rv-sub">{{ __('Original transaction: :journal · Amount: :amount', ['journal' => $req?->journalEntry?->journal_number ?? '—', 'amount' => number_format($req?->journalEntry?->total_debit ?? 0, 2)]) }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-btn rv-btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                {{ __('Back to Queue') }}
            </a>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Transaction Detail --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Original Transaction Details') }}</span>
                </div>
                <div class="rv-detail">
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Journal Number') }}</span>
                        <span class="rv-detail-value rv-mono">{{ $req?->journalEntry?->journal_number ?? '—' }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Date') }}</span>
                        <span class="rv-detail-value">{{ $req?->journalEntry?->date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Amount') }}</span>
                        <span class="rv-detail-value rv-numr">{{ number_format($req?->journalEntry?->total_debit ?? 0, 2) }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Reversal Method') }}</span>
                        <span class="rv-detail-value">{{ ($req?->reversal_method ?? '') === 'full' ? __('Full Reversal') : __('Partial — ') . number_format($req?->partial_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="rv-detail-item">
                        <span class="rv-detail-label">{{ __('Source Module') }}</span>
                        <span class="rv-detail-value">{{ ucfirst(str_replace('_', ' ', $req?->original_transaction_type ?? '')) }}</span>
                    </div>
                </div>

                @if($req?->journalEntry?->lines)
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
                                @foreach($req->journalEntry->lines as $line)
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
                                    <td class="rv-right rv-numr">{{ number_format($req->journalEntry->total_debit, 2) }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($req->journalEntry->total_credit, 2) }}</td>
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
                <p style="font-size:.875rem;line-height:1.6;color:var(--deep-3)">{{ $req?->reason }}</p>
            </div>

            {{-- Authorization Chain --}}
            <div class="rv-card" style="margin-top:1rem">
                <div class="rv-card-head">
                    <span class="rv-card-title">{{ __('Authorization Chain') }}</span>
                </div>
                @if($req?->authorizationRequests?->count() > 0)
                    <div class="rv-chain">
                        @foreach($req->authorizationRequests as $authItem)
                            <div class="rv-chain-item {{ $authItem->status === 'approved' ? 'is-done' : '' }}">
                                <span class="rv-chain-level">Level {{ $authItem->approval_level }}</span>
                                <span class="rv-chain-assignee">{{ $authItem->assignee?->name ?? '—' }}</span>
                                <span class="rv-chain-status">
                                    <span class="rv-badge rv-badge--{{ $authItem->status === 'approved' ? 'approved' : ($authItem->status === 'rejected' ? 'rejected' : 'pending') }}">
                                        <span class="rv-dot"></span>
                                        {{ ucfirst($authItem->status) }}
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rv-mono" style="padding:0 1.25rem">{{ __('No authorization steps configured.') }}</p>
                @endif
            </div>

            {{-- Approve/Reject Forms --}}
            @if(in_array($authorization->status, ['pending']))
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Your Decision') }}</span>
                    </div>

                    {{-- Approve --}}
                    <form method="POST" action="{{ route('accounting.reversals.auth.approve', $authorization->id) }}" id="approveForm" style="margin-bottom:1rem">
                        @csrf
                        <div class="rv-field" style="margin-bottom:.75rem">
                            <label class="rv-label">{{ __('Remarks (optional)') }}</label>
                            <textarea name="comments" class="rv-textarea" placeholder="{{ __('Add approval remarks...') }}"></textarea>
                        </div>
                        <button type="submit" class="rv-btn rv-btn--approve">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ __('Approve Reversal') }}
                        </button>
                    </form>

                    <div style="border-top:1px solid var(--line);margin:1rem 0"></div>

                    {{-- Reject --}}
                    <form method="POST" action="{{ route('accounting.reversals.auth.reject', $authorization->id) }}" id="rejectForm">
                        @csrf
                        <div class="rv-field" style="margin-bottom:.75rem">
                            <label class="rv-label">{{ __('Rejection Reason') }} *</label>
                            <textarea name="reason" class="rv-textarea" required placeholder="{{ __('Provide a reason for rejection...') }}" minlength="10"></textarea>
                            @error('reason')<span class="rv-error">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit" class="rv-btn rv-btn--reject" data-fb-confirm="return confirm('Are you sure you want to reject this reversal request?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            {{ __('Reject Reversal') }}
                        </button>
                    </form>
                </div>
            @endif

            {{-- Already Approved --}}
            @if($authorization->status === 'approved')
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Authorization Completed') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1.25rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <div>
                            <span style="font-weight:600;color:#15803d">{{ __('This authorization has been approved.') }}</span>
                            @if($req?->reversal)
                                <span style="font-size:.8125rem;color:var(--muted);margin-left:.5rem">{{ __('Reversal journal entry') }} {{ $req->reversal?->reversalJournalEntry?->journal_number }} {{ __('has been posted.') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Rejected --}}
            @if($authorization->status === 'rejected')
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Authorization Rejected') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1.25rem;background:#fee2e2;border:1px solid #fca5a5;border-radius:12px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        <span style="font-weight:600;color:#b91c1c">{{ __('This authorization has been rejected.') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.auth.show', $authorization->id) }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></span>
                        {{ __('Approve') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth.queue') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></span>
                        {{ __('Pending Queue') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
