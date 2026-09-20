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
         @keydown.escape.window="closeAll()">

        <div class="jr-phead">
            <div>
                <h1>Journals</h1>
                <div class="sub">Open a journal to review every line — finalize, post, reopen and reverse are confirm-gated.</div>
            </div>
            <div class="jr-acts">
                <span class="jr-me">Signed in as {{ auth()->user()?->name ?? '—' }} · {{ strtoupper(substr(auth()->user()?->name ?? '—', 0, 1) . (str_contains(auth()->user()?->name ?? '', ' ') ? substr(strrchr(auth()->user()->name, ' '), 1, 1) : '')) }}</span>
                <a href="{{ route('accounting.journal-entries.export', request()->query()) }}" class="jr-btn jr-btn-g">⤓ Export</a>
                <a href="{{ route('accounting.journal-entries.create') }}" class="jr-btn jr-btn-p">＋ New Journal</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.journal-entries.index') }}" class="jr-filters">
            <div class="jr-frow">
                <div class="jr-mode">
                    <button type="button" :class="filterMode === 'range' ? 'on' : ''" @click="setMode('range')">Date range</button>
                    <button type="button" :class="filterMode === 'period' ? 'on' : ''" @click="setMode('period')">Period</button>
                    <input type="hidden" name="mode" :value="filterMode">
                </div>

                <div x-show="filterMode === 'range'">
                    <label class="jr-lbl">From</label>
                    <input class="jr-in" type="date" name="date_from" x-ref="jrFrom"
                           value="{{ $filters['date_from'] }}"
                           :disabled="filterMode === 'period'"
                           @change="syncBounds()">
                </div>

                <div x-show="filterMode === 'range'">
                    <label class="jr-lbl">To</label>
                    <input class="jr-in" type="date" name="date_to" x-ref="jrTo"
                           value="{{ $filters['date_to'] }}"
                           :disabled="filterMode === 'period'"
                           @change="syncBounds()">
                </div>

                <div x-show="filterMode === 'period'">
                    <label class="jr-lbl">Period</label>
                    <select class="jr-in" name="period" :disabled="filterMode !== 'period'">
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
                    <input class="jr-in" type="text" name="search" placeholder="№, memo or reference…" value="{{ $filters['search'] }}">
                </div>

                <input type="hidden" name="status" value="{{ $activeTab === 'all' ? '' : $activeTab }}">

                <button type="button" class="jr-btn jr-btn-p"
                        @click="if (filterMode === 'range' && !validateDates()) return; $el.form.submit()">Apply</button>
                <a href="{{ route('accounting.journal-entries.index') }}" class="jr-btn jr-btn-g">Clear</a>
            </div>
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
                                $isMine = (int) $entry->created_by === (int) auth()->id();
                                $creatorName = $entry->createdBy?->name ?? '—';
                                $sub = match (true) {
                                    $entry->status === 'draft' => 'Draft · creator ' . $creatorName,
                                    in_array($entry->status, ['pending_approval', 'approved'], true) => 'Finalized · awaiting post',
                                    $entry->status === 'reversed' => 'Reversed by ' . ($entry->reversalEntry?->journal_number ?? 'a reversal'),
                                    $entry->source_module === 'reversal' && $entry->status === 'posted' => 'Mirrored lines · posted',
                                    default => $entry->reference ?? '',
                                };
                                $pillClass = match ($entry->status) {
                                    'draft' => 'unfin',
                                    'pending_approval', 'approved' => 'unpost',
                                    'posted' => 'posted',
                                    default => 'reversed',
                                };
                                $pillLabel = match ($entry->status) {
                                    'draft' => 'Unfinalized',
                                    'pending_approval', 'approved' => 'Unposted',
                                    'posted' => 'Posted',
                                    default => 'Reversed',
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
                                <td class="num">{{ format_number($entry->total_debit) }}</td>
                                <td><span class="jr-pill {{ $pillClass }}">● {{ $pillLabel }}</span></td>
                                <td>
                                    <div class="jr-rowact">
                                        <button type="button" class="jr-ib" title="Open journal" @click="open({{ $entry->id }}, 'view')">👁</button>

                                        @if($entry->status === 'draft' && $can['finalize'])
                                            <button type="button" class="jr-ib"
                                                    @if($isMine) title="Open & edit (creator)" @click="open({{ $entry->id }}, 'edit')"
                                                    @else disabled title="Only the creator ({{ $creatorName }}) can edit" @endif>✎</button>
                                            <button type="button" class="jr-ib del"
                                                    @if($isMine && $can['delete']) title="Delete" @click="openDelete({{ $entry->id }})"
                                                    @else disabled title="Only the creator ({{ $creatorName }}) can delete" @endif>🗑</button>
                                        @elseif(in_array($entry->status, ['pending_approval', 'approved'], true) && $can['finalize'])
                                            <button type="button" class="jr-ib" title="Reopen" @click="openReopen({{ $entry->id }})">⤺</button>
                                        @elseif($entry->status === 'posted')
                                            @if($can['reverse'])
                                                <button type="button" class="jr-ib rev" title="Reverse" x-show="viewed[{{ $entry->id }}]" @click="openReverse({{ $entry->id }})">⟲</button>
                                            @endif
                                            <button type="button" class="jr-ib" title="Print" @click="printEntry({{ $entry->id }})">🖨</button>
                                        @elseif($entry->status === 'reversed')
                                            <button type="button" class="jr-ib" title="Open reversal" @click="openReversal({{ $entry->id }})">⟲</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px">
                                    No journals match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="jr-tfoot">
                <span>{{ $journalEntries->total() }} {{ \Illuminate\Support\Str::plural('journal', $journalEntries->total()) }}</span>
                <span>Showing {{ $journalEntries->firstItem() ?? 0 }}–{{ $journalEntries->lastItem() ?? 0 }} of {{ $journalEntries->total() }}</span>
            </div>
        </div>

        @include('accounting.journal-entries._jr-modals', ['preserved' => $preserved])

        <div class="jr-toast" :class="{ on: toastOpen }" x-text="toastMsg"></div>
    </div>
</x-app-layout>
