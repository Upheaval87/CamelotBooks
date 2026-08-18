<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.cost-centers.index') }}">Cost Centres</a> <span>›</span> <span class="here">{{ $costCenter->code }} — {{ $costCenter->name }}</span>
            </nav>
        </div>

        <div class="ac-card" style="margin-bottom:22px">
            <div class="ac-card-h">
                <h2>Details</h2>
            </div>
            <div class="ac-pad">
                <div class="ac-g2">
                    <div><span class="ac-tchip">Code</span> {{ $costCenter->code }}</div>
                    <div><span class="ac-tchip">Name</span> {{ $costCenter->name }}</div>
                    <div><span class="ac-tchip">Department</span> {{ $costCenter->department ?? '—' }}</div>
                    <div><span class="ac-tchip">Manager</span> {{ $costCenter->manager ?? '—' }}</div>
                    <div><span class="ac-tchip">Status</span> {{ $costCenter->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <h2>Transactions</h2>
                <div class="right">
                    <span class="ac-okchip">{{ number_format($actualDebit, 2) }} / {{ number_format($actualCredit, 2) }}</span>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($costCenter->journalEntryLines as $line)
                        <tr>
                            <td class="ac-em">{{ $line->journalEntry?->date?->format('d M Y') ?? '—' }}</td>
                            <td class="ac-mono"><a href="{{ route('accounting.journal-entries.show', $line->journalEntry) }}" style="color:var(--ac-deep-1);text-decoration:none;font-weight:600;font-size:12.5px">{{ $line->journalEntry?->journal_number ?? '—' }}</a></td>
                            <td class="ac-mono">{{ $line->account?->code ?? '—' }}</td>
                            <td class="ac-em">{{ $line->memo ?? '—' }}</td>
                            <td class="ac-numr">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                            <td class="ac-numr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="ac-em" style="text-align:center;padding:40px">No transactions for this cost centre.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($costCenter->journalEntryLines->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="4" style="font-weight:800">Total</td>
                            <td class="ac-numr bold">{{ number_format($actualDebit, 2) }}</td>
                            <td class="ac-numr bold">{{ number_format($actualCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
