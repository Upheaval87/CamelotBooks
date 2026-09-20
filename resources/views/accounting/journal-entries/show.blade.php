<x-app-layout>
    <div class="je2-wrap">
        {{-- Reversal state — derived server-side --}}
        @php
            $isPosted = $journalEntry->isPosted();
            $isReversed = $journalEntry->isReversed();
            $isDraftEntry = $journalEntry->isDraft();
            $isPending = $journalEntry->isPendingApproval();
            $hasPendingDraft = (bool) $pendingReversal;
            $suspendReverse = $hasPendingDraft;

            $statusClass = match($journalEntry->status) {
                'pending_approval' => 'je2-badge je2-b-pend',
                'approved' => 'je2-badge je2-b-post',
                'posted' => 'je2-badge je2-b-post',
                'reversed' => 'je2-badge je2-b-rev',
                default => 'je2-badge je2-b-draft',
            };
            $statusLabel = match($journalEntry->status) {
                'draft' => 'Draft',
                'pending_approval' => 'Pending Approval',
                'approved' => 'Approved',
                'posted' => 'Posted',
                'reversed' => 'Reversed',
                default => ucfirst($journalEntry->status),
            };
            $canReverse = auth()->user()?->can('journal-entries.reverse') ?? false;
            $identityThreshold = \App\Services\Accounting\JournalReversalService::identityVerifyThreshold();
            $reversalTotal = (float) $journalEntry->total_debit;
            $needsIdentity = $identityThreshold !== null && $reversalTotal >= $identityThreshold;
            $reversalJournals = $journalEntry->reversingEntries()
                ->whereIn('status', ['draft', 'posted'])
                ->orderByDesc('id')
                ->get();
            $reversalLink = $appliedReversal ?? ($pendingReversal ?? null);
            $timelineEvents = $journalEntry->auditLogs
                ->sortByDesc('created_at')
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name ?? 'System',
                    'at' => $log->created_at,
                    'is_reversal' => str_contains(strtolower($log->action), 'revers'),
                ])
                ->values();
        @endphp

        <div class="je2-crumbs">
            <a href="{{ route('accounting.journal-entries.index') }}">Journals</a>
            <span>›</span>
            <span class="here">{{ $journalEntry->journal_number }}</span>
        </div>

        <div class="je2-page-head">
            <div>
                <div class="je2-headline">
                    <h1>Journal Entry</h1>
                    <span class="je2-ref">{{ $journalEntry->journal_number }}</span>
                    <span class="{{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span>
                </div>
                <div class="sub">{{ $journalEntry->date->format('d M Y') }}
                    @if($journalEntry->branch_id) · {{ $journalEntry->branch?->name ?? 'Branch' }} @endif
                    · Source: {{ $journalEntry->source_module ?: 'Manual entry' }}
                </div>
            </div>
            <div class="je2-actions">
                @if($isDraftEntry)
                <a href="{{ route('accounting.journal-entries.edit', $journalEntry) }}" class="je2-btn je2-btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    Edit
                </a>
                @endif
                @if($isPosted && $canReverse)
                <button type="button" class="je2-btn je2-btn-ghost"
                        @if($suspendReverse) disabled title="A reversal draft is pending for this entry." @else
                        x-on:click="$dispatch('open-modal', 'reversal-modal')" @endif>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    Reverse
                </button>
                @endif
                <a href="{{ route('accounting.journal-entries.index') }}" class="je2-btn je2-btn-ghost">Back</a>
            </div>
        </div>

        {{-- Reversal banner --}}
        @if($isReversed && $appliedReversal)
        <div class="je2-revbanner je2-revbanner--applied">
            <span class="je2-revicon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </span>
            <div>
                <strong>This journal entry has been reversed.</strong>
                <span class="muted">Offset by
                    <a href="{{ route('accounting.journal-entries.show', $appliedReversal) }}">{{ $appliedReversal->journal_number }}</a>
                    @if($appliedReversal->memo) — {{ $appliedReversal->memo }} @endif</span>
            </div>
            <a href="{{ route('accounting.journal-entries.show', $appliedReversal) }}" class="je2-btn je2-btn-ghost je2-btn-sm">View reversal</a>
        </div>
        @elseif($isReversed && $appliedReversal === null && $reversalJournals->isNotEmpty())
        <div class="je2-revbanner je2-revbanner--applied">
            <span class="je2-revicon">⟲</span>
            <div><strong>This journal entry has been reversed.</strong>
                <span class="muted">Offset by <a href="{{ route('accounting.journal-entries.show', $reversalJournals->first()) }}">{{ $reversalJournals->first()->journal_number }}</a></span>
            </div>
            <a href="{{ route('accounting.journal-entries.show', $reversalJournals->first()) }}" class="je2-btn je2-btn-ghost je2-btn-sm">View reversal</a>
        </div>
        @elseif($hasPendingDraft)
        <div class="je2-revbanner je2-revbanner--draft">
            <span class="je2-revicon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </span>
            <div>
                <strong>A reversal draft is pending for this entry.</strong>
                <span class="muted">
                    <a href="{{ route('accounting.journal-entries.show', $pendingReversal) }}">{{ $pendingReversal->journal_number }}</a>
                    @if($pendingReversal->memo) — {{ $pendingReversal->memo }} @endif
                </span>
            </div>
            <div class="je2-banner-actions">
                <form method="POST" action="{{ route('accounting.journal-entries.post-reversal', $journalEntry) }}" class="inline">
                    @csrf
                    <button type="submit" class="je2-btn je2-btn-sm">Post reversal</button>
                </form>
                <form method="POST" action="{{ route('accounting.journal-entries.discard-reversal', $journalEntry) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Discard this reversal draft?', {type:'danger'})">
                    @csrf
                    <button type="submit" class="je2-btn je2-btn-sm je2-btn-danger-outline">Discard</button>
                </form>
            </div>
        </div>
        @endif

        <div class="je2-shell">
            <div class="je2-main">
                {{-- Journal Lines card --}}
                <div class="je2-card">
                    <div class="je2-card-h">
                        <h2>Journal Lines</h2>
                        <div class="right">
                            @if($journalEntry->reference)
                            <span class="je2-tchip">{{ $journalEntry->reference }}</span>
                            @endif
                            <span class="je2-tchip">{{ $journalEntry->is_adjusting_entry ? 'Adjusting' : 'General' }}</span>
                            <span class="je2-tchip">{{ $journalEntry->date->format('d M Y') }}</span>
                        </div>
                    </div>
                    <div class="je2-li-wrap">
                        <table class="je2-table">
                            <thead>
                                <tr>
                                    <th style="width:30%">Account</th>
                                    <th style="width:30%">Description</th>
                                    <th class="num" style="width:13%">Debit</th>
                                    <th class="num" style="width:13%">Credit</th>
                                    <th style="width:14%">Cost Centre</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($journalEntry->lines as $line)
                                <tr>
                                    <td>
                                        <span class="je2-acct">
                                            <span class="code">{{ $line->account?->code ?? '—' }}</span>
                                            <span class="name">{{ $line->account?->name ?? '' }}</span>
                                        </span>
                                    </td>
                                    <td class="je2-em">{{ $line->memo ?? '—' }}</td>
                                    <td class="num">{{ $line->debit > 0 ? format_number((float) $line->debit, 2) : '—' }}</td>
                                    <td class="num">{{ $line->credit > 0 ? format_number((float) $line->credit, 2) : '—' }}</td>
                                    <td class="je2-em">{{ $line->costCenter?->code ?? '—' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="je2-em" style="text-align:center;padding:24px">No lines.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Totals</td>
                                    <td class="num">{{ format_number((float) $journalEntry->total_debit, 2) }}</td>
                                    <td class="num">{{ format_number((float) $journalEntry->total_credit, 2) }}</td>
                                    <td>
                                        @if(abs($journalEntry->total_debit - $journalEntry->total_credit) < 0.01)
                                        <span class="je2-okchip">✓ Balanced</span>
                                        @else
                                        <span class="je2-okchip bad">Out {{ format_number(abs($journalEntry->total_debit - $journalEntry->total_credit), 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="je2-foot-meta">
                        @if($journalEntry->createdBy)
                        Created by {{ $journalEntry->createdBy->name }} · {{ $journalEntry->created_at?->format('d M Y H:i') }}
                        @endif
                        @if($journalEntry->postedByUser)
                        · Posted by {{ $journalEntry->postedByUser->name }} · {{ $journalEntry->posted_at?->format('d M Y H:i') }}
                        @endif
                    </div>
                </div>

                {{-- Description card --}}
                <div class="je2-card">
                    <div class="je2-card-h"><h2>Description</h2></div>
                    <div class="je2-pad">
                        <p class="je2-desc">{{ $journalEntry->memo ?: 'No description provided for this journal entry.' }}</p>
                    </div>
                </div>
            </div>

            <aside class="je2-rail">
                {{-- Activity --}}
                <div class="je2-rail-card">
                    <div class="je2-rail-h">
                        <span class="je2-rail-ic">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </span>
                        Feedback
                    </div>
                    <div class="je2-tl">
                        @forelse($timelineEvents->take(8) as $event)
                        <div class="je2-tl-item {{ $event['is_reversal'] ? 'rev' : '' }}">
                            <div class="je2-tl-dot"></div>
                            <div class="je2-tl-body">
                                <div class="je2-tl-action">{{ ucwords(str_replace('_', ' ', $event['action'])) }}</div>
                                <div class="je2-tl-meta">{{ $event['user'] }} · {{ $event['at']?->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="je2-tl-empty">Activity feedback will appear here.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Linked documents --}}
                <div class="je2-rail-card">
                    <div class="je2-rail-h">
                        <span class="je2-rail-ic">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        Linked Documents
                    </div>
                    <div class="je2-docs">
                        @if($isReversed && $appliedReversal)
                        <a class="je2-doc je2-doc--rev" href="{{ route('accounting.journal-entries.show', $appliedReversal) }}">
                            <span class="je2-doc-ic">⟲</span>
                            <span>
                                <span class="je2-doc-title">{{ $appliedReversal->journal_number }}</span>
                                <span class="je2-doc-sub">Reversal · {{ $appliedReversal->date->format('d M Y') }}</span>
                            </span>
                        </a>
                        @endif
                        @if($hasPendingDraft)
                        <a class="je2-doc je2-doc--draft" href="{{ route('accounting.journal-entries.show', $pendingReversal) }}">
                            <span class="je2-doc-ic">⟲</span>
                            <span>
                                <span class="je2-doc-title">{{ $pendingReversal->journal_number }}</span>
                                <span class="je2-doc-sub">Reversal draft · pending</span>
                            </span>
                        </a>
                        @endif
                        @if($isPosted && $canReverse && ! $suspendReverse)
                        <a class="je2-doc je2-doc--action" href="#" x-on:click.prevent="$dispatch('open-modal', 'reversal-modal')">
                            <span class="je2-doc-ic">+</span>
                            <span>
                                <span class="je2-doc-title">Reverse this entry</span>
                                <span class="je2-doc-sub">Create an offsetting journal</span>
                            </span>
                        </a>
                        @endif
                        @if($reversalJournals->isEmpty() && $appliedReversal === null && $pendingReversal === null)
                        <div class="je2-doc-empty">No linked documents.</div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Reversal modal --}}
    @if($isPosted && $canReverse && ! $suspendReverse)
    <x-modal name="reversal-modal" maxWidth="lg">
        <div class="je2-modal">
            <div class="je2-modal-h">
                <div class="je2-modal-ic">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </div>
                <div>
                    <h3>Reverse {{ $journalEntry->journal_number }}</h3>
                    <p class="je2-modal-sub">Create an offsetting journal entry that mirrors these lines in reverse.</p>
                </div>
            </div>

            <div class="je2-modal-warn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                Reversing is irreversible in effect: the original entry will be marked <strong>Reversed</strong> the moment the reversal entry is posted.
            </div>

            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" id="reversal-form">
                @csrf
                <div class="je2-modal-grid">
                    <div class="je2-f">
                        <label for="reversal_date">Reversal date</label>
                        <input class="in" type="date" id="reversal_date" name="reversal_date" value="{{ old('reversal_date', now()->format('Y-m-d')) }}" required>
                        @if($period && ! $period->isOpen())
                        <p class="je2-hint warn">The original period ({{ $period->label }}) is closed — the reversal will post to the selected date instead.</p>
                        @endif
                    </div>
                    <div class="je2-f">
                        <label for="reference">Reference <span class="opt">(optional)</span></label>
                        <input class="in" type="text" id="reference" name="reference" value="{{ old('reference', 'REV-' . $journalEntry->journal_number) }}" maxlength="60">
                    </div>
                </div>
                <div class="je2-f">
                    <label for="memo">Reason</label>
                    <textarea class="in area" id="memo" name="memo" required maxlength="1000" placeholder="e.g. Posted to the wrong date, duplicate posting, client refund…">{{ old('memo') }}</textarea>
                </div>

                <div class="je2-modal-preview">
                    <div class="je2-prev-h">Offsetting lines</div>
                    <div class="je2-prev-row head">
                        <span>Account</span><span class="num">Debit</span><span class="num">Credit</span>
                    </div>
                    @foreach($journalEntry->lines as $line)
                    <div class="je2-prev-row">
                        <span>{{ $line->account?->code ?? '—' }} · {{ $line->account?->name ?? '' }}</span>
                        <span class="num">{{ $line->credit > 0 ? format_number((float) $line->credit, 2) : '—' }}</span>
                        <span class="num">{{ $line->debit > 0 ? format_number((float) $line->debit, 2) : '—' }}</span>
                    </div>
                    @endforeach
                    <div class="je2-prev-total">Total {{ format_number((float) $journalEntry->total_debit, 2) }}</div>
                </div>

                <div class="je2-modal-radio">
                    <label class="je2-radio">
                        <input type="radio" name="post_mode" value="immediate" checked>
                        <span>
                            <strong>Post immediately</strong>
                            <small>Creates and posts the reversal in one step.</small>
                        </span>
                    </label>
                    <label class="je2-radio">
                        <input type="radio" name="post_mode" value="draft">
                        <span>
                            <strong>Save as draft</strong>
                            <small>Create the reversal as a draft to review before posting.</small>
                        </span>
                    </label>
                </div>

                @if($needsIdentity)
                <label class="je2-radio je2-radio--identity">
                    <input type="checkbox" name="identity_confirm" value="1" required>
                    <span>
                        <strong>Confirm identity</strong>
                        <small>This reversal is at or above the verification threshold ({{ format_number($reversalTotal, 2) }}). Confirm to proceed.</small>
                    </span>
                </label>
                @endif

                <div class="je2-modal-actions">
                    <button type="button" class="je2-btn je2-btn-ghost" x-on:click="$dispatch('close-modal', 'reversal-modal')">Cancel</button>
                    <button type="submit" class="je2-btn je2-btn-danger">Create reversal</button>
                </div>
            </form>
        </div>
    </x-modal>
    @endif
</x-app-layout>