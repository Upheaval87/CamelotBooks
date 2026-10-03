@php
    $preserved = collect(request()->query())
        ->only(['status', 'search', 'type', 'branch_id', 'date_from', 'date_to', 'period', 'mode', 'page'])
        ->filter(fn ($v) => $v !== null && $v !== '')
        ->all();

    $tabBase = collect(request()->query())->except(['status', 'page'])->all();
    $tabUrl = fn (?string $key) => route(
        'accounting.journal-entries.index',
        $key ? array_merge($tabBase, ['status' => $key]) : $tabBase
    );

    $activeTab = $filters['status_key'];

    $tabs = [
        'all' => ['label' => 'All', 'count' => $stats['all']],
        'unfinalized' => ['label' => 'Unfinalized', 'count' => $stats['unfinalized']],
        'unposted' => ['label' => 'Unposted · Finalized', 'count' => $stats['unposted']],
        'posted' => ['label' => 'Posted', 'count' => $stats['posted']],
        'reversed' => ['label' => 'Reversed', 'count' => $stats['reversed']],
    ];

    /* R5 / §2.5 — a reversed journal is opened, never printed from the row. */
    $printableStatuses = ['posted'];

    $jrConfig = [
        'entries' => $payload,
        'can' => $can,
        'me' => (int) auth()->id(),
        'defaultAccount' => $defaultAccount,
    ];
@endphp

<x-app-layout>
    <div class="jr-wrap"
         x-data="journalRegister({{ Js::from($jrConfig) }})"
         @keydown.escape.window="closeTop()">

        <div class="jr-phead">
            <div>
                <h1>Journals</h1>
            </div>
            <div class="jr-acts">
                <a href="{{ route('accounting.journal-entries.export', request()->query()) }}" class="jr-btn jr-btn-g">⤓ Export</a>
                <a href="{{ route('accounting.journal-entries.create') }}" class="jr-btn jr-btn-p">＋ New Journal</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.journal-entries.index') }}" class="jr-filters">
            <input type="hidden" name="mode" value="{{ $filters['mode'] }}">

            <div class="jr-frow">
                <div class="jr-mode">
                    <button type="button" :class="filterMode === 'range' ? 'on' : ''" @click="setMode('range')">Date range</button>
                    <button type="button" :class="filterMode === 'period' ? 'on' : ''" @click="setMode('period')">Period</button>
                </div>

                <div x-show="filterMode === 'range'">
                    <label class="jr-lbl">From</label>
                    <input class="jr-in {{ $dateError ? 'is-bad' : '' }}" type="date" name="date_from" x-ref="jrFrom"
                           value="{{ $filters['date_from'] }}"
                           @change="syncBounds()">
                </div>

                <div x-show="filterMode === 'range'">
                    <label class="jr-lbl">To</label>
                    <input class="jr-in {{ $dateError ? 'is-bad' : '' }}" type="date" name="date_to" x-ref="jrTo"
                           value="{{ $filters['date_to'] }}"
                           @change="syncBounds()">
                </div>

                <div x-show="filterMode === 'period'">
                    <label class="jr-lbl">Period</label>
                    <select class="jr-in" name="period">
                        @foreach($periodOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['period'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="jr-lbl">Type</label>
                    <select class="jr-in" name="type">
                        <option value="">All types</option>
                        @foreach($typeOptions as $typeOption)
                            <option value="{{ $typeOption }}" {{ $filters['type'] === $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="jr-lbl">Branch</label>
                    <select class="jr-in" name="branch_id">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (int) $filters['branch_id'] === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="jr-lbl">Search</label>
                    <input class="jr-in" type="text" name="search" placeholder="№, description or reference…" value="{{ $filters['search'] }}">
                </div>

                <input type="hidden" name="status" value="{{ $activeTab === 'all' ? '' : $activeTab }}">

                <button type="button" class="jr-btn jr-btn-p"
                        @click="if (filterMode === 'range' && !validateDates()) return; $el.form.submit()">Apply</button>
                <a href="{{ route('accounting.journal-entries.index') }}" class="jr-btn jr-btn-g">Clear</a>
            </div>

            {{-- §2.2: an inverted range is reported server-side and NOT applied. --}}
            @if($dateError)
                <p class="jr-fail" role="alert">{{ $dateError }}</p>
            @endif
        </form>

        <div class="jr-tabs">
            @foreach($tabs as $key => $tab)
                <a href="{{ $tabUrl($key === 'all' ? null : $key) }}"
                   class="jr-tab {{ $activeTab === $key ? 'on' : '' }}">{{ $tab['label'] }} <span class="n">{{ $tab['count'] }}</span></a>
            @endforeach
        </div>

        <div class="jr-card">
            <div class="jr-li-wrap">
                <table class="jr-table">
                    <thead>
                        <tr>
                            <th>Journal №</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Source</th>
                            <th class="num">Lines</th>
                            <th class="num">Total ({{ $cs }})</th>
                            <th>Status</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journalEntries as $entry)
                            @php
                                $pillClass = match ($entry->status) {
                                    'draft' => 'unfinalized',
                                    'pending_approval', 'approved' => 'unposted',
                                    'posted' => 'posted',
                                    default => 'reversed',
                                };
                                $pillLabel = match ($entry->status) {
                                    'draft' => 'Unfinalized',
                                    'pending_approval', 'approved' => 'Unposted',
                                    'posted' => 'Posted',
                                    default => 'Reversed',
                                };

                                /* R2: attribution is a bare timestamp or a plain
                                     reference — never "by <actor>" prefixes. */
                                $sub = match (true) {
                                    $entry->status === 'draft' => 'Draft',
                                    in_array($entry->status, ['pending_approval', 'approved'], true) => 'Finalized · awaiting post',
                                    $entry->status === 'reversed' => 'Reversed',
                                    $entry->source_module === 'reversal' && $entry->status === 'posted' => 'Mirrored lines · posted',
                                    default => $entry->reference ?? '',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <span class="jr-jno" @click="open({{ $entry->id }}, 'view')">{{ $entry->journal_number }}</span>
                                </td>
                                <td>{{ $entry->date?->format('d M Y') }}</td>
                                <td>{{ \App\Services\Accounting\JournalTypeClassifier::label($entry->source_module, (bool) $entry->is_adjusting_entry) }}</td>
                                <td class="desc">
                                    {{ $entry->memo ?: ($entry->reference ?? '—') }}
                                    @if($sub !== '')<small>{{ $sub }}</small>@endif
                                </td>
                                <td>{{ $entry->source_module ?: 'manual' }}</td>
                                <td class="num">{{ $entry->lines_count }}</td>
                                <td class="num">{{ format_number($entry->total_debit, $decimals) }}</td>
                                <td><span class="jr-pill {{ $pillClass }}">● {{ $pillLabel }}</span></td>
                                <td>
                                    {{-- R5 / §2.5: view always; print only for a posted
                                         journal. Every lifecycle action lives in the
                                         journal modal footer. Reversed rows are
                                         open-only — no reverse icon, no print icon. --}}
                                    <div class="jr-rowact">
                                        <button type="button" class="jr-ib" title="Open journal" @click="open({{ $entry->id }}, 'view')">👁</button>
                                        @if(in_array($entry->status, $printableStatuses, true))
                                            <a class="jr-ib" title="Print voucher" target="_blank" rel="noopener" href="{{ route('accounting.journal-entries.print', $entry->id) }}">🖨</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="jr-empty">
                                        <strong>No journals match the current filters.</strong>
                                        <span>Adjust or clear the filters to see the ledger.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- §2.6 --}}
            <div class="jr-tfoot">
                <span>Showing {{ $journalEntries->firstItem() ?? 0 }}–{{ $journalEntries->lastItem() ?? 0 }} of {{ $journalEntries->total() }} journals</span>
            </div>
        </div>

        @include('accounting.journal-entries._jr-modals', [
            'entries' => $payload,
            'can' => $can,
            'preserved' => $preserved,
            'decimals' => $decimals,
            'cs' => $cs,
        ])

        <div class="jr-toast" :class="{ on: toastOpen }" x-text="toastMsg" role="status" aria-live="polite"></div>
    </div>
</x-app-layout>