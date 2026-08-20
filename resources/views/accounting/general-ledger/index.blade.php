<x-app-layout>
    <div class="gl-suite">
        <div class="gl-wrap">
            <div class="gl-page-head">
                <div>
                    <h1>General Ledger</h1>
                    <div class="sub">Search and view posted journal entries by date range, account, or reference.</div>
                </div>
                <a href="{{ route('accounting.general-ledger.export-csv', request()->query()) }}" class="btn btn-ghost">Export CSV</a>
            </div>

            <div class="gl-card gl-mb">
                <div class="gl-pad">
                    <form method="GET" class="gl-fgrid" id="gl-filter-form">
                        <div class="gl-f">
                            <label>Account</label>
                            <select class="in" name="account_id">
                                <option value="">All accounts</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}" {{ request('account_id') == $a->id ? 'selected' : '' }}>{{ $a->code }} · {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="gl-f">
                            <label>From</label>
                            <input class="in" type="date" name="date_from" value="{{ request('date_from', '') }}">
                        </div>
                        <div class="gl-f">
                            <label>To</label>
                            <input class="in" type="date" name="date_to" value="{{ request('date_to', '') }}">
                        </div>
                        <button type="submit" class="btn btn-sec" style="height:42px">Apply Filters</button>
                        <a href="{{ route('accounting.general-ledger.index') }}" class="btn btn-ghost" style="height:42px">Clear</a>
                    </form>
                </div>
            </div>

            <div class="gl-card">
                <div class="gl-card-h">
                    <span class="ic">📒</span>
                    <h2>Transactions</h2>
                    <div class="right">
                        <span class="gl-chip">{{ $glStats['transactions'] }} entries · {{ $glStats['accounts'] }} accounts</span>
                    </div>
                </div>
                <div class="gl-li-wrap">
                    <table class="gl-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal</th>
                                <th style="width:26%">Account</th>
                                <th>Description</th>
                                <th class="num">Debit</th>
                                <th class="num">Credit</th>
                                <th class="num">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($glPaginator as $entry)
                            @php
                                $line = $entry['line'];
                                $rb = $entry['running_balance'];
                            @endphp
                            <tr>
                                <td class="gl-em">{{ $line->journalEntry?->date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if($line->journalEntry)
                                    <a class="gl-jl gl-mono" href="{{ route('accounting.journal-entries.show', $line->journalEntry) }}">{{ $line->journalEntry->journal_number ?? '—' }}</a>
                                    @else
                                        <span class="gl-mono gl-em">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="gl-mono">{{ $line->account?->code ?? '—' }}</span>
                                    <span class="gl-name">{{ $line->account?->name ?? '' }}</span>
                                </td>
                                <td class="gl-em">{{ $line->memo ?? '—' }}</td>
                                <td class="num">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                <td class="num">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                                <td class="num {{ $rb < -0.01 ? 'gl-neg' : '' }}">{{ number_format($rb, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="gl-empty">
                                    <div class="e">📒</div>
                                    No transactions found for the selected filters.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($glPaginator) > 0)
                        <tfoot>
                            <tr>
                                <td colspan="4">Total</td>
                                <td class="num">{{ number_format($glStats['debit'], 2) }}</td>
                                <td class="num">{{ number_format($glStats['credit'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                @if(method_exists($glPaginator, 'links') && $glPaginator->hasPages())
                <div class="gl-footer-bar">
                    <span>Page {{ $glPaginator->currentPage() }} of {{ $glPaginator->lastPage() }}</span>
                    <div>{{ $glPaginator->links() }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
