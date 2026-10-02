<x-app-layout>
    {{--
        JOURNAL ENTRY DETAIL + PRINT VOUCHER
        Appendix-A sheet. Presentation only: every existing endpoint, payload,
        permission check and modal hook below is unchanged (open/close-modal,
        #reversal-form, post-reversal, discard-reversal, fbConfirmSubmit).
    --}}
    {{-- `x-data` is load-bearing: layouts/app.blade.php has no body-level x-data,
         and Alpine 3 only initialises from [x-data] roots
         (Alpine.start() -> querySelectorAll(allSelectors())), so the four
         x-on:click handlers below (open the reversal modal from the toolbar and
         the backdrop tile, close it from the header/Cancel) stay dead without an
         Alpine root of their own. Empty object, no state. --}}
    <div id="glj-app" class="glj" x-data="{}">
        @php
            /* ── reversal / lifecycle state (unchanged, derived server-side) ───── */
            $isPosted = $journalEntry->isPosted();
            $isReversed = $journalEntry->isReversed();
            $isDraftEntry = $journalEntry->isDraft();
            $hasPendingDraft = (bool) $pendingReversal;
            $suspendReverse = $hasPendingDraft;
            $canReverse = auth()->user()?->can('journal-entries.reverse') ?? false;
            $identityThreshold = \App\Services\Accounting\JournalReversalService::identityVerifyThreshold();
            $reversalTotal = (float) $journalEntry->total_debit;
            $needsIdentity = $identityThreshold !== null && $reversalTotal >= $identityThreshold;
            $reversalJournals = $journalEntry->reversingEntries()
                ->whereIn('status', ['draft', 'posted'])
                ->orderByDesc('id')
                ->get();

            /* ── status presentation ──────────────────────────────────────────── */
            $statusLabel = match ($journalEntry->status) {
                'draft' => 'Draft',
                'pending_approval' => 'Pending Approval',
                'approved' => 'Approved',
                'posted' => 'Posted',
                'reversed' => 'Reversed',
                default => ucfirst(str_replace('_', ' ', (string) $journalEntry->status)),
            };
            /* Only a posted entry reads as a live ledger movement, so only it
               pulses; reversed is amber, everything else is neutral (no pulse). */
            $eyeClass = match (true) {
                $isReversed => 'glj-eye warn',
                $isPosted => 'glj-eye',
                default => 'glj-eye neutral',
            };

            /* ── R6: money always comes from company/system settings ──────────
             * $decimals and $dateFormat arrive from JournalEntryController::show(),
             * the same settings voucherPayload() reads for the print page — one
             * source of truth, so the screen and the printed sheet always agree
             * to the cent. */
            $decimals = $decimals ?? 2;
            $dateFormat = $dateFormat ?: 'Y-m-d';
            $fmtMoney = fn ($value) => format_number((float) $value, $decimals);
            $fmtDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format($dateFormat) : '—';

            /* ── totals for the Journal Lines card footer ─────────────────────── */
            $totalDebit = (float) $journalEntry->total_debit;
            $totalCredit = (float) $journalEntry->total_credit;
            $variance = abs($totalDebit - $totalCredit);
            $isBalanced = $variance < 0.01;
            $lineCount = $journalEntry->lines->count();

            /* ── R3: activity feed = action + timestamp only, no actor name ───── */
            $timelineEvents = $journalEntry->auditLogs
                ->sortByDesc('created_at')
                ->take(8)
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'label' => ucwords(str_replace('_', ' ', (string) $log->action)),
                    'at' => $log->created_at,
                    'notes' => $log->notes ?? null,
                    'is_reversal' => str_contains(strtolower((string) $log->action), 'revers'),
                ])
                ->values();
        @endphp

        <div class="glj-wrap">
            {{-- 2.1 breadcrumb --}}
            <nav class="glj-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('accounting.journal-entries.index') }}">Journals</a>
                <svg class="sep" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                <span class="cur">{{ $journalEntry->journal_number }}</span>
            </nav>

            {{-- 2.2 page head (R7: light card, no dark gradient, no KPI tiles) --}}
            <header class="glj-phead">
                <div>
                    <span class="{{ $eyeClass }}"><i></i>{{ $statusLabel }}</span>
                    <h1>
                        Journal Entry
                        <span class="glj-refchip">
                            {{ $journalEntry->journal_number }}
                            <button type="button" data-glj-copy="{{ $journalEntry->journal_number }}"
                                    aria-label="Copy journal number" title="Copy journal number">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                            </button>
                        </span>
                    </h1>
                    <div class="glj-hsub">
                        <b>{{ $fmtDate($journalEntry->date) }}</b>
                        @if ($journalEntry->branch_id)
                            <span class="sep"></span>{{ $journalEntry->branch?->name ?: 'Branch' }}
                        @endif
                        <span class="sep"></span>
                        <span class="src">{{ $typeLabel }}</span>
                    </div>
                </div>

                <div class="glj-ctl">
                    @if ($isDraftEntry)
                        <a href="{{ route('accounting.journal-entries.edit', $journalEntry) }}" class="glj-btn">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            Edit
                        </a>
                    @endif
                    @if ($isPosted && $canReverse)
                        <button type="button"
                                class="glj-btn danger {{ $suspendReverse ? 'dis' : '' }}"
                                @if ($suspendReverse) disabled title="A reversal draft is pending for this entry."
                                @else x-on:click="$dispatch('open-modal', 'reversal-modal')" @endif>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            Reverse
                        </button>
                    @endif
                    {{-- Opens the standalone A4 voucher page in a NEW TAB. That page
                         is itself the print preview (live sheet, one toolbar) and
                         owns Print + Download PDF. rel=noopener because
                         target=_blank hands the new document a window.opener;
                         data-glj-print-url is read by journal-detail.js so the
                         Ctrl/Cmd+P path opens the same page instead of printing
                         this detail screen's chrome. --}}
                    <a href="{{ route('accounting.journal-entries.print', $journalEntry) }}"
                       target="_blank"
                       rel="noopener"
                       data-glj-print-url="{{ route('accounting.journal-entries.print', $journalEntry) }}"
                       class="glj-btn"
                       id="glj-print-voucher">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                        Print Voucher
                    </a>
                    <a href="{{ route('accounting.journal-entries.index') }}" class="glj-btn">Back to journal</a>
                </div>
            </header>

            {{-- Reversal banners (same states, same endpoints as before) --}}
            @if ($isReversed && $appliedReversal)
                <div class="glj-card glj-tl" style="--d:40ms">
                    <div class="glj-warn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        <p>
                            <strong>This journal entry has been reversed.</strong>
                            Offset by
                            <a href="{{ route('accounting.journal-entries.show', $appliedReversal) }}">{{ $appliedReversal->journal_number }}</a>
                            @if ($appliedReversal->memo) — {{ $appliedReversal->memo }} @endif
                        </p>
                    </div>
                </div>
            @elseif ($hasPendingDraft)
                <div class="glj-card glj-tl" style="--d:40ms">
                    <div class="glj-warn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <p>
                            <strong>A reversal draft is pending for this entry.</strong>
                            <a href="{{ route('accounting.journal-entries.show', $pendingReversal) }}">{{ $pendingReversal->journal_number }}</a>
                            @if ($pendingReversal->memo) — {{ $pendingReversal->memo }} @endif
                        </p>
                    </div>
                    <div class="glj-m-foot" style="border-top:none;background:transparent;padding:12px 0 0">
                        <form method="POST" action="{{ route('accounting.journal-entries.post-reversal', $journalEntry) }}">
                            @csrf
                            <button type="submit" class="glj-btn">Post reversal</button>
                        </form>
                        <form method="POST" action="{{ route('accounting.journal-entries.discard-reversal', $journalEntry) }}"
                              onsubmit="return fbConfirmSubmit(event, 'Discard this reversal draft?', { type: 'danger' })">
                            @csrf
                            <button type="submit" class="glj-btn danger">Discard</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- 2.6 two-column layout: 1.55fr / 340px --}}
            <div class="glj-grid">
                <div class="glj-col">
                    {{-- 2.3 journal lines --}}
                    <section class="glj-card" style="--d:60ms">
                        <div class="glj-card-h">
                            <h2 class="glj-card-title"><i></i>Journal Lines</h2>
                            <div class="glj-chips">
                                <span class="glj-chip mono">{{ $lineCount }} {{ $lineCount === 1 ? 'entry' : 'entries' }}</span>
                                @if ($journalEntry->reference)
                                    <span class="glj-chip mono">{{ $journalEntry->reference }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="glj-lwrap">
                            <table class="glj-table">
                                <thead>
                                    <tr>
                                        <th style="width:30%">Account</th>
                                        <th style="width:32%">Description</th>
                                        <th class="r" style="width:15%">Debit ({{ $cs }})</th>
                                        <th class="r" style="width:15%">Credit ({{ $cs }})</th>
                                        <th style="width:8%">Cost Centre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($journalEntry->lines as $line)
                                        <tr>
                                            <td>
                                                <div class="glj-acct">
                                                    <span class="glj-dot {{ strtolower((string) ($line->account?->type ?? 'asset')) }}"></span>
                                                    <span>
                                                        <span class="code">{{ $line->account?->code ?: '—' }}</span>
                                                        <span class="aname">{{ $line->account?->name ?: '' }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="glj-dcell">{{ $line->memo ?: '—' }}</td>
                                            <td class="glj-num">{!! $line->debit > 0 ? $fmtMoney($line->debit) : '<span class="glj-dash">—</span>' !!}</td>
                                            <td class="glj-num">{!! $line->credit > 0 ? $fmtMoney($line->credit) : '<span class="glj-dash">—</span>' !!}</td>
                                            <td class="glj-dcell">{{ $line->costCenter?->code ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="text-align:center;padding:26px" class="glj-tl-empty">No lines.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="glj-totals">
                                        <td colspan="2">
                                            <span class="tl-l">Totals</span>
                                            @if ($isBalanced)
                                                <span class="glj-bal" style="margin-left:10px">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Balanced
                                                </span>
                                            @else
                                                <span class="glj-bal bad" style="margin-left:10px">Out by {{ $fmtMoney($variance) }}</span>
                                            @endif
                                        </td>
                                        <td class="glj-num">{{ $fmtMoney($totalDebit) }}</td>
                                        <td class="glj-num">{{ $fmtMoney($totalCredit) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        {{-- R3: attributions are bare timestamps, no actor prefix --}}
                        <div class="glj-tmeta">
                            <span>Created <b>{{ $fmtDate($journalEntry->created_at) }}</b></span>
                            @if ($journalEntry->posted_at)
                                <span class="sep"></span>
                                <span>Posted <b>{{ $fmtDate($journalEntry->posted_at) }}</b></span>
                            @endif
                            @if ($journalEntry->updated_at && $journalEntry->updated_at->ne($journalEntry->created_at))
                                <span class="sep"></span>
                                <span>Updated <b>{{ $fmtDate($journalEntry->updated_at) }}</b></span>
                            @endif
                        </div>
                    </section>

                    {{-- 2.4 description (R5: always "Description", never "Memo") --}}
                    <section class="glj-card" style="--d:120ms">
                        <div class="glj-card-h"><h2 class="glj-card-title"><i></i>Description</h2></div>
                        <div class="glj-descbody">{{ $journalEntry->memo ?: 'No description provided for this journal entry.' }}</div>
                    </section>
                </div>

                {{-- 2.5 rail --}}
                <aside class="glj-col">
                    <section class="glj-card" style="--d:160ms">
                        <div class="glj-card-h"><h2 class="glj-card-title"><i></i>Activity</h2></div>
                        <div class="glj-tl">
                            @forelse ($timelineEvents as $event)
                                <div class="glj-tnode {{ $event['is_reversal'] ? 'warn' : '' }}">
                                    <span class="td"></span>
                                    <span>
                                        <span class="tt">{{ $event['label'] }}</span>
                                        <span class="ts">{{ $fmtDate($event['at']) }}</span>
                                    </span>
                                </div>
                            @empty
                                <div class="glj-tl-empty">Activity will appear here.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="glj-card" style="--d:200ms">
                        <div class="glj-card-h"><h2 class="glj-card-title"><i></i>Linked Documents</h2></div>
                        <div class="glj-lk">
                            @if ($isReversed && $appliedReversal)
                                <a class="glj-lk-tile" href="{{ route('accounting.journal-entries.show', $appliedReversal) }}">
                                    <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></span>
                                    <span>
                                        <span class="t">{{ $appliedReversal->journal_number }}</span>
                                        <span class="s">Reversal · {{ $fmtDate($appliedReversal->date) }}</span>
                                    </span>
                                </a>
                            @endif
                            @if ($hasPendingDraft)
                                <a class="glj-lk-tile" href="{{ route('accounting.journal-entries.show', $pendingReversal) }}">
                                    <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg></span>
                                    <span>
                                        <span class="t">{{ $pendingReversal->journal_number }}</span>
                                        <span class="s">Reversal draft · pending</span>
                                    </span>
                                </a>
                            @endif
                            @if ($isPosted && $canReverse && ! $suspendReverse)
                                <a class="glj-lk-tile" href="#" x-on:click.prevent="$dispatch('open-modal', 'reversal-modal')">
                                    <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></span>
                                    <span>
                                        <span class="t">Reverse this entry</span>
                                        <span class="s">Create an offsetting journal</span>
                                    </span>
                                </a>
                            @endif
                            @if (! $isReversed && ! $hasPendingDraft && ! ($isPosted && $canReverse))
                                <div class="glj-lk-empty">No linked documents.</div>
                            @endif
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    {{--
        4.1 PRINT VOUCHER
        The A4 sheet no longer lives in this page. "Print Voucher" above opens
        accounting.journal-entries.print in a NEW TAB; that page renders the same
        sheet (now _voucher-sheet.blade.php) as a live preview and owns Print +
        Download PDF. The in-page overlay and its print pipeline were removed with
        it so there is exactly one print path.
    --}}

    {{-- 3. Reversal modal — same name, same hook, same endpoint, same fields. --}}
    @if ($isPosted && $canReverse && ! $suspendReverse)
        <x-modal name="reversal-modal" maxWidth="lg">
            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" id="reversal-form">
                @csrf

                <div class="glj-m-head">
                    <span class="glj-m-ic">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </span>
                    <span class="glj-m-head-left">
                        <span class="glj-m-title">Reverse {{ $journalEntry->journal_number }}</span>
                    </span>
                    <button type="button" class="glj-m-close" x-on:click="$dispatch('close-modal', 'reversal-modal')" aria-label="Close">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="glj-m-body">
                    {{-- Mirror preview: what the engine will create (debit/credit swapped). --}}
                    <div class="glj-mirr">
                        <div class="mh">Offsetting lines</div>
                        @foreach ($journalEntry->lines as $line)
                            <div class="glj-mline">
                                <span class="side {{ $line->credit > 0 ? 'cr' : 'dr' }}">{{ $line->credit > 0 ? 'Dr' : 'Cr' }}</span>
                                <span class="code">{{ $line->account?->code ?: '—' }}</span>
                                <span class="name">{{ $line->account?->name ?: '' }}</span>
                                <span class="amt">{{ $line->credit > 0 ? $fmtMoney($line->credit) : $fmtMoney($line->debit) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="glj-fgrid">
                        {{-- Both are readonly: the reversal date defaults to the entry
                             date and the reference is derived from it, so neither is
                             user-editable. Readonly (not disabled) so both still post
                             to the controller, which keeps validating them. --}}
                        <div class="glj-fld">
                            <label for="reversal_date">Reversal Date</label>
                            <input type="date" id="reversal_date" name="reversal_date" readonly
                                   value="{{ old('reversal_date', $journalEntry->date->format('Y-m-d')) }}" required>
                            @error('reversal_date')<p class="err">{{ $message }}</p>@enderror
                            @if ($period && ! $period->isOpen())
                                <p class="hint">Period “{{ $period->label }}” is closed — the reversal posts to the date above.</p>
                            @endif
                        </div>
                        <div class="glj-fld">
                            <label for="reference">Reference</label>
                            <input type="text" id="reference" name="reference" readonly
                                   value="{{ old('reference', 'REV-' . $journalEntry->journal_number) }}" maxlength="60">
                            @error('reference')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div class="glj-fld full">
                            <label for="memo">Reason</label>
                            <input type="text" id="memo" name="memo" required maxlength="1000"
                                   placeholder="e.g. Posted to the wrong date, duplicate posting, client refund…"
                                   value="{{ old('memo') }}">
                            @error('memo')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- post_mode is still a required controller input (|in:immediate,draft),
                         but the chooser is gone: every reversal from this modal posts
                         immediately, so the value is fixed rather than selectable. --}}
                    <input type="hidden" name="post_mode" value="immediate">

                    @if ($needsIdentity)
                        {{-- Server validates identity_confirm === 'accepted'. --}}
                        <div class="glj-fld" style="margin-top:15px">
                            <label class="glj-btn" style="border:none;box-shadow:none;background:none;height:auto;padding:0;justify-content:flex-start">
                                <input type="checkbox" name="identity_confirm" value="accepted" required>
                                <span>
                                    <strong style="display:block">Confirm identity</strong>
                                    <span class="hint">This reversal is at or above the verification threshold ({{ $fmtMoney($reversalTotal) }}).</span>
                                </span>
                            </label>
                            @error('identity_confirm')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <div class="glj-m-foot">
                    <button type="button" class="glj-mbtn ghost" x-on:click="$dispatch('close-modal', 'reversal-modal')">Cancel</button>
                    <span class="spacer"></span>
                    <button type="submit" class="glj-mbtn danger">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        Create reversal
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
