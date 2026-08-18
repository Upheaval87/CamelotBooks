<x-app-layout>
    <div class="je-wrap">
        <div class="je-page-head">
            <div>
                <h1>Journal Entries</h1>
                <div class="sub">All manual postings with status and actions.</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.report-center.index') }}" class="je-btn je-btn-ghost">Import / Export</a>
                <a href="{{ route('accounting.journal-entries.create') }}" class="je-btn je-btn-cta">＋ New Journal Entry</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.journal-entries.index') }}">
            <div class="je-card" style="margin-bottom:16px">
                <div class="je-pad">
                    <div class="je-toolbar">
                        <input class="in grow" name="search" placeholder="Search journal № / description…" value="{{ request('search') }}">
                        <select class="in" name="status">
                            <option value="">All Status</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>Reversed</option>
                        </select>
                        <select class="in" name="type">
                            <option value="">All Types</option>
                            <option value="general" {{ request('type') === 'general' ? 'selected' : '' }}>General</option>
                            <option value="adjusting" {{ request('type') === 'adjusting' ? 'selected' : '' }}>Adjusting</option>
                        </select>
                        <input class="in" type="date" name="date_from" value="{{ request('date_from') }}">
                        <input class="in" type="date" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="je-btn je-btn-sec">Filter</button>
                        @if(request()->hasAny(['search','status','type','date_from','date_to']))
                        <a href="{{ route('accounting.journal-entries.index') }}" class="je-btn je-btn-ghost">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <div class="je-card">
            <div class="je-li-wrap">
                <table class="je-table">
                    <thead>
                        <tr>
                            <th>Journal No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="num">Amount</th>
                            <th>Status</th>
                            <th>Prepared By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journalEntries as $entry)
                        <tr>
                            <td class="je-mono">{{ $entry->journal_number }}</td>
                            <td class="je-em">{{ $entry->date->format('d M Y') }}</td>
                            <td><span class="je-tchip">{{ $entry->is_adjusting_entry ? 'Adjusting' : 'General' }}</span></td>
                            <td>{{ $entry->memo ?: ($entry->reference ?? '—') }}</td>
                            <td class="num">{{ number_format($entry->total_debit, 2) }}</td>
                            <td>
                                @php
                                    $statusClass = match($entry->status) {
                                        'draft' => 'je-b-draft',
                                        'pending_approval' => 'je-b-pend',
                                        'approved' => 'je-b-post',
                                        'posted' => 'je-b-post',
                                        'reversed' => 'je-b-rev',
                                        default => 'je-b-draft',
                                    };
                                    $statusLabel = match($entry->status) {
                                        'pending_approval' => 'Pending',
                                        default => ucfirst($entry->status),
                                    };
                                @endphp
                                <span class="je-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span>
                            </td>
                            <td class="je-em">{{ $entry->createdBy?->name ?? '—' }}</td>
                            <td class="je-row-act">
                                <a href="{{ route('accounting.journal-entries.show', $entry) }}" class="je-ibtn" title="View">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if($entry->isDraft())
                                <a href="{{ route('accounting.journal-entries.edit', $entry) }}" class="je-ibtn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                @endif
                                @if($entry->isPosted())
                                <form method="POST" action="{{ route('accounting.journal-entries.reverse', $entry) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, 'Create a reversal entry for {{ $entry->journal_number }}?', {type:'danger'})">
                                    @csrf
                                    <button type="submit" class="je-ibtn" title="Reverse">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="je-em" style="text-align:center;padding:40px">No journal entries found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="je-pager">
                <span>Showing {{ $journalEntries->firstItem() ?? 0 }}–{{ $journalEntries->lastItem() ?? 0 }} of {{ $journalEntries->total() }}</span>
                <span style="margin-left:auto"></span>
                @if($journalEntries->onFirstPage())
                <span class="pg disabled">‹</span>
                @else
                <a href="{{ $journalEntries->previousPageUrl() }}" class="pg">‹</a>
                @endif
                @if($journalEntries->hasMorePages())
                <a href="{{ $journalEntries->nextPageUrl() }}" class="pg">›</a>
                @else
                <span class="pg disabled">›</span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
