<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>Account Budgets & Journals</h1><div class="sub">Read-only budgets from the Budgeting module; journals posted through the existing handler.</div></div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.budgets.dashboard') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Open Budgeting →</a>
            </div>
        </div>

        <div class="coa-card" style="margin-bottom:16px">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Account Budgets</h2>
                <span class="tchip" style="margin-left:8px;background:rgba(18,143,142,.10);border-color:rgba(18,143,142,.35);color:var(--sec)">links to built Budgeting</span>
                <div style="margin-left:auto"><a href="{{ route('accounting.budgets.dashboard') }}" class="open-l" style="font-size:11px;font-weight:800;color:var(--sec);text-decoration:none">Open Budgeting →</a></div>
            </div>
            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead><tr><th>Code</th><th>Account</th><th class="num">Budget</th><th class="num">Actual YTD</th><th class="num">Variance</th><th>Util</th></tr></thead>
                    <tbody>
                        @forelse($accounts->take(15) as $account)
                        @php $actual = $balances[$account->id] ?? 0; @endphp
                        <tr>
                            <td class="coa-mono">{{ $account->display_code }}</td>
                            <td style="font-weight:700;color:var(--ink)">{{ $account->name }}</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">—</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ number_format($actual, 2) }}</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--green)">—</td>
                            <td><span class="tchip">—</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="coa-empty">No posting accounts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Journal Entries</h2>
                <div style="margin-left:auto"><a href="{{ route('accounting.journal-entries.index') }}" class="open-l" style="font-size:11px;font-weight:800;color:var(--sec);text-decoration:none">View All →</a></div>
            </div>
            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead><tr><th>Journal</th><th>Date</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentJournals as $journal)
                        <tr>
                            <td class="coa-mono">{{ $journal->reference }}</td>
                            <td class="coa-em">{{ $journal->date?->format('d M Y') ?? '—' }}</td>
                            <td class="coa-em">{{ $journal->description ?? $journal->memo ?? '—' }}</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ number_format($journal->journalEntryLines->sum('debit'), 2) }}</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ number_format($journal->journalEntryLines->sum('credit'), 2) }}</td>
                            <td>
                                <span class="tchip {{ $journal->status === 'posted' ? 'post' : ($journal->status === 'reversed' ? 'dr' : 'lv') }}">
                                    {{ ucfirst($journal->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="coa-empty">No journal entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
