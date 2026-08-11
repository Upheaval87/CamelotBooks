@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $statusFilter = request('status');
    $jeStats = [
        'total' => array_sum($stats->toArray() ?? []),
        'draft' => (int) ($stats['draft'] ?? 0),
        'pending_approval' => (int) ($stats['pending_approval'] ?? 0),
        'posted' => (int) ($stats['posted'] ?? 0),
        'reversed' => (int) ($stats['reversed'] ?? 0),
    ];
@endphp
<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Journal Entries</h1>
                        <div class="sub">Post manual journal entries to the general ledger.</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.journal-entries.create') }}" class="btn cta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            New Journal Entry
                        </a>
                    </div>
                </div>

                <div class="shell">
                    <div>

                        {{-- Portfolio / stats --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                                <h2>Journal</h2>
                                <span class="rule"></span>
                            </div>

                            <div class="sgrid">
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                                    <div>
                                        <div class="l">Total Entries</div>
                                        <div class="v">{{ number_format($jeStats['total']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Posted</div>
                                        <div class="v">{{ number_format($jeStats['posted']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Pending Approval</div>
                                        <div class="v">{{ number_format($jeStats['pending_approval']) }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- filters --}}
                            <form method="GET" action="{{ route('accounting.journal-entries.index') }}" id="je-list-form">
                                <div class="controls">
                                    <select name="status" class="input">
                                        <option value="">All Statuses</option>
                                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                        <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                                        <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>Reversed</option>
                                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                    <input type="date" name="date_from" class="input" value="{{ request('date_from') }}" title="Date from" />
                                    <input type="date" name="date_to" class="input" value="{{ request('date_to') }}" title="Date to" />
                                    <x-scoped-search-field
                                        name="branch_id"
                                        entity="branch"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                        :value="request('branch_id')"
                                        :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                                        placeholder="{{ __('Search branches...') }}"
                                    />
                                    <button type="submit" class="btn ghost">Filter</button>
                                    @if(request()->hasAny('status', 'date_from', 'date_to', 'branch_id'))
                                        <a href="{{ route('accounting.journal-entries.index') }}" class="btn ghost">Clear</a>
                                    @endif
                                    <span class="chip-t">{{ $journalEntries->total() }} entries</span>
                                </div>
                            </form>
                        </section>

                        {{-- entry list --}}
                        <section class="card" style="padding:20px 24px; margin-top:16px">
                            <div class="li-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:13%">Journal #</th>
                                            <th style="width:10%">Date</th>
                                            <th style="width:24%">Description</th>
                                            <th class="num" style="width:12%">Debit ({{ $cs }})</th>
                                            <th class="num" style="width:12%">Credit ({{ $cs }})</th>
                                            <th style="width:13%">Status</th>
                                            <th style="width:9%">Branch</th>
                                            <th style="width:7%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($journalEntries as $je)
                                        <tr>
                                            <td><a href="{{ route('accounting.journal-entries.show', $je) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $je->journal_number }}</a></td>
                                            <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $je->date->format('M d, Y') }}</td>
                                            <td class="em">{{ $je->memo ?? '—' }}</td>
                                            <td class="numr">{{ format_number($je->total_debit) }}</td>
                                            <td class="numr">{{ format_number($je->total_credit) }}</td>
                                            <td>
                                                @if($je->status === 'draft')
                                                    <span class="badge b-draft"><span class="bdot"></span>Draft</span>
                                                @elseif($je->status === 'pending_approval')
                                                    <span class="badge b-teal"><span class="bdot"></span>Pending Approval</span>
                                                @elseif($je->status === 'posted')
                                                    <span class="badge b-post"><span class="bdot"></span>Posted</span>
                                                @elseif($je->status === 'reversed')
                                                    <span class="badge b-void"><span class="bdot"></span>Reversed</span>
                                                @elseif($je->status === 'rejected')
                                                    <span class="badge b-red"><span class="bdot"></span>Rejected</span>
                                                @else
                                                    <span class="badge b-gray"><span class="bdot"></span>{{ ucfirst($je->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="em">{{ $je->branch->name ?? '—' }}</td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.journal-entries.show', $je) }}" class="ibtn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                                    @if($je->status === 'draft')
                                                        @can('journal-entries.submit')
                                                            <form method="POST" action="{{ route('accounting.journal-entries.submit-for-approval', $je) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Submit this entry for approval?', { type: 'action' })">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sec btn-sm">Submit</button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                    @if($je->status === 'pending_approval')
                                                        @can('journal-entries.approve')
                                                            <form method="POST" action="{{ route('accounting.journal-entries.approve', $je) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Approve and post this entry?', { type: 'action' })">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sec btn-sm">Approve</button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                    @if($je->status === 'posted')
                                                        @can('journal-entries.reverse')
                                                            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $je) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Reverse this entry?', { type: 'danger' })">
                                                                @csrf
                                                                <button type="submit" class="btn btn-danger-o btn-sm">Reverse</button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8"><div class="empty">No journal entries found.</div></td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                @if($journalEntries->hasPages())
                                    @php
                                        $paginator = $journalEntries->appends(request()->query());
                                        $last = $paginator->lastPage();
                                        $cur = $paginator->currentPage();
                                        $winStart = max(1, $cur - 2);
                                        $winEnd = min($last, $cur + 2);
                                        $firstItem = $paginator->firstItem() ?: 0;
                                        $lastItem = $paginator->lastItem() ?: 0;
                                    @endphp
                                    <div class="pagi">
                                        <span class="t">Showing {{ $firstItem }}–{{ $lastItem }} of {{ $paginator->total() }} entries</span>
                                        <span class="pg">
                                            @if($paginator->onFirstPage())
                                                <span class="pgbtn" aria-disabled="true" aria-label="Previous">‹</span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">‹</a>
                                            @endif

                                            @if($winStart > 1)
                                                <a href="{{ $paginator->url(1) }}">1</a>
                                                @if($winStart > 2)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                            @endif

                                            @for($page = $winStart; $page <= $winEnd; $page++)
                                                @if($page === $cur)
                                                    <span class="pgbtn cur" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($winEnd < $last)
                                                @if($winEnd < $last - 1)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                                <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
                                            @endif

                                            @if($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next">›</a>
                                            @else
                                                <span class="pgbtn" aria-disabled="true" aria-label="Next">›</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    {{-- right rail --}}
                    <aside class="railsum">
                        <div class="card">
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                    <h2>Views</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.journal-entries.index') }}" class="vitem {{ !$statusFilter ? 'on' : '' }}" {{ !$statusFilter ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                                        All Entries
                                    </a>
                                    <a href="{{ route('accounting.journal-entries.index', ['status' => 'draft']) }}" class="vitem {{ $statusFilter === 'draft' ? 'on' : '' }}" {{ $statusFilter === 'draft' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                                        Draft
                                    </a>
                                    <a href="{{ route('accounting.journal-entries.index', ['status' => 'pending_approval']) }}" class="vitem {{ $statusFilter === 'pending_approval' ? 'on' : '' }}" {{ $statusFilter === 'pending_approval' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Pending Approval
                                    </a>
                                    <a href="{{ route('accounting.journal-entries.index', ['status' => 'posted']) }}" class="vitem {{ $statusFilter === 'posted' ? 'on' : '' }}" {{ $statusFilter === 'posted' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Posted
                                    </a>
                                    <a href="{{ route('accounting.journal-entries.index', ['status' => 'reversed']) }}" class="vitem {{ $statusFilter === 'reversed' ? 'on' : '' }}" {{ $statusFilter === 'reversed' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Reversed
                                    </a>
                                </div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                    <h2>Reports</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.general-ledger.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                        General Ledger
                                    </a>
                                    <a href="{{ route('accounting.trial-balance.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7l3-3 4 4 4-4 4 4 3-3M3 7h18"/></svg></span>
                                        Trial Balance
                                    </a>
                                    <a href="{{ route('accounting.accounts.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg></span>
                                        Chart of Accounts
                                    </a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
