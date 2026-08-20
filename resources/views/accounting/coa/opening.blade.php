<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>Opening Balances</h1><div class="sub">Set starting balances before first posting period.</div></div>
            <div style="display:flex;gap:10px">
                <button class="coa-btn coa-btn-ghost coa-btn-sm">Import</button>
                <button class="coa-btn coa-btn-ghost coa-btn-sm" style="background:var(--sec);color:#fff">Post Opening</button>
            </div>
        </div>

        <div class="coa-card" style="margin-bottom:16px">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Opening Balances</h2>
                @php
                    $totalDebit = $accounts->sum(fn($a) => max(0, $balances[$a->id] ?? 0));
                    $totalCredit = $accounts->sum(fn($a) => max(0, -($balances[$a->id] ?? 0)));
                    $isBalanced = abs($totalDebit - $totalCredit) < 0.01;
                @endphp
                <span class="okchip" style="{{ $isBalanced ? '' : 'background:rgba(185,28,28,.08);border-color:rgba(185,28,28,.3);color:var(--red-2)' }}">
                    {{ $isBalanced ? '✓ Balanced · Dr = Cr' : '⚠ Out of balance' }}
                </span>
            </div>
            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead><tr><th>Code</th><th>Account</th><th>Class</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
                    <tbody>
                        @forelse($accounts as $account)
                        @php $bal = $balances[$account->id] ?? 0; @endphp
                        <tr>
                            <td class="coa-mono">{{ $account->display_code }}</td>
                            <td style="font-weight:700;color:var(--ink)">{{ $account->name }}</td>
                            <td><span class="tchip">{{ ucfirst($account->type) }}</span></td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ $bal > 0 ? number_format($bal, 2) : '—' }}</td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ $bal < 0 ? number_format(abs($bal), 2) : '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="coa-empty">No posting accounts to set balances for.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="font-weight:800">Totals</td>
                            <td class="num" style="text-align:right;font-weight:800">{{ number_format($totalDebit, 2) }}</td>
                            <td class="num" style="text-align:right;font-weight:800">{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
