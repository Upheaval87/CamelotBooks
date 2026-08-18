<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.general-ledger.index') }}">General Ledger</a> <span>›</span> <span class="here">{{ $account->code }} — {{ $account->name }}</span>
            </nav>
        </div>

        <div class="ac-card" style="margin-bottom:22px">
            <div class="ac-pad">
                <form method="GET" class="ac-g4">
                    <div class="ac-f">
                        <label>Account</label>
                        <input class="in" value="{{ $account->code }} — {{ $account->name }}" disabled>
                    </div>
                    <div class="ac-f">
                        <label>From</label>
                        <input class="in" type="date" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="ac-f">
                        <label>To</label>
                        <input class="in" type="date" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="ac-f">
                        <label>&nbsp;</label>
                        <button type="submit" class="ac-btn ac-btn-ghost ac-btn-sm">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <h2>Transactions</h2>
                <div class="right">
                    <span class="ac-tchip">Dr {{ number_format($accountStats['debit'], 2) }} · Cr {{ number_format($accountStats['credit'], 2) }}</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th style="width:11%">Date</th>
                            <th style="width:10%">Journal</th>
                            <th style="width:12%">Account</th>
                            <th style="width:22%">Description</th>
                            <th class="num" style="width:12%">Debit</th>
                            <th class="num" style="width:12%">Credit</th>
                            <th class="num" style="width:12%">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:rgba(17,69,75,.04)">
                            <td class="ac-em" colspan="5">Opening Balance</td>
                            <td class="ac-numr bold" colspan="2">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                        @forelse($transactionsPaginator as $entry)
                        @php
                            $line = $entry['line'];
                            $rb = $entry['running_balance'];
                        @endphp
                        <tr>
                            <td class="ac-em">{{ $line->journalEntry?->date?->format('d M Y') ?? '—' }}</td>
                            <td class="ac-mono"><a href="{{ route('accounting.journal-entries.show', $line->journalEntry) }}" style="color:var(--ac-deep-1);text-decoration:none;font-weight:600;font-size:12.5px">{{ $line->journalEntry?->journal_number ?? '—' }}</a></td>
                            <td class="ac-mono">{{ $line->account?->code ?? '—' }}</td>
                            <td class="ac-em">{{ $line->memo ?? '—' }}</td>
                            <td class="ac-numr">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                            <td class="ac-numr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            <td class="ac-numr bold">{{ number_format($rb, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="ac-em" style="text-align:center;padding:40px">No entries for this account.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="font-weight:800">Total</td>
                            <td class="ac-numr bold">{{ number_format($accountStats['debit'], 2) }}</td>
                            <td class="ac-numr bold">{{ number_format($accountStats['credit'], 2) }}</td>
                            <td class="ac-numr bold">{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
