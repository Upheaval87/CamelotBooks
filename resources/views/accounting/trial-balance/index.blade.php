<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Trial Balance</h1>
            <div class="ac-sub">Summary of all account balances for a period.</div>
        </div>

        <div class="ac-card" style="margin-bottom:22px">
            <div class="ac-pad">
                <form method="GET" class="ac-g4">
                    <div class="ac-f">
                        <label>As of Date</label>
                        <input class="in" type="date" name="date" value="{{ request('date', $asOfDate ?? date('Y-m-d')) }}">
                    </div>
                    <div class="ac-f">
                        <label>&nbsp;</label>
                        <div style="display:flex;gap:8px">
                            <button type="submit" class="ac-btn ac-btn-ghost ac-btn-sm" style="flex:1">Show Trial Balance</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <h2>All Accounts</h2>
                <div class="right">
                    @php
                        $isBalanced = abs($difference) < 0.01;
                    @endphp
                    <span class="ac-okchip {{ $isBalanced ? '' : 'bad' }}">{{ $isBalanced ? '&#10003; Balanced' : '&#10007; Out of Balance' }}</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th style="width:15%">Account</th>
                            <th style="width:35%">Name</th>
                            <th style="width:15%">Type</th>
                            <th class="num" style="width:18%">Debit</th>
                            <th class="num" style="width:18%">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance as $row)
                        <tr>
                            <td class="ac-mono">{{ $row['account']->code ?? '—' }}</td>
                            <td class="ac-em">{{ $row['account']->name ?? '—' }}</td>
                            <td class="ac-em">{{ $row['account']->type ?? '—' }}</td>
                            <td class="ac-numr">{{ ($row['debit_balance'] ?? 0) > 0 ? number_format($row['debit_balance'], 2) : '—' }}</td>
                            <td class="ac-numr">{{ ($row['credit_balance'] ?? 0) > 0 ? number_format($row['credit_balance'], 2) : '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="ac-em" style="text-align:center;padding:40px">No data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($trialBalance) > 0)
                    <tfoot>
                        <tr>
                            <td colspan="3" style="font-weight:800">Total</td>
                            <td class="ac-numr bold">{{ number_format($totalDebit, 2) }}</td>
                            <td class="ac-numr bold">{{ number_format($totalCredit, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
