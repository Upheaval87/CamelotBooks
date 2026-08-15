<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation Register</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; color: #0B2A2D; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { padding: 40px 48px; }
        .head { background: linear-gradient(135deg, #17565D, #0C3539 60%, #0A2E32); border-radius: 14px; padding: 28px 32px; color: #fff; display: flex; align-items: center; justify-content: space-between; margin-bottom: 26px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo { width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #149897, #128F8E); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: #EAFFFF; }
        .brand-name { font-weight: 700; font-size: 15px; letter-spacing: .02em; }
        .doc { text-align: right; }
        .doc-title { font-size: 22px; font-weight: 800; letter-spacing: -0.01em; }
        .doc-meta { font-size: 11px; color: rgba(226,244,244,.75); margin-top: 4px; }
        h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #5F7476; margin: 24px 0 10px; }
        .summary { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
        .sum-box { border: 1px solid #DCEAEA; border-radius: 10px; padding: 14px 16px; }
        .sum-box .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #5F7476; margin-bottom: 6px; }
        .sum-box .val { font-size: 14px; font-weight: 700; }
        .sum-box.alt { background: linear-gradient(135deg, #149897, #128F8E); border: none; color: #fff; }
        .sum-box.alt .lbl { color: rgba(226,244,244,.8); }
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; text-align: left; padding: 9px 10px; background: #11454B; color: #E2F4F4; }
        th.num, td.num { text-align: right; font-variant-numeric: tabular-nums; }
        td { font-size: 12px; padding: 9px 10px; border-bottom: 1px solid #E2ECEC; }
        tr.total td { font-weight: 700; background: #F0F6F6; border-bottom: none; }
        .foot { margin-top: 28px; padding-top: 14px; border-top: 1px solid #DCEAEA; font-size: 10px; color: #8AA5A7; text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <div class="page">
        <div class="head">
            <div class="brand">
                <div class="logo">CB</div>
                <div class="brand-name">{{ $company->name }}</div>
            </div>
            <div class="doc">
                <div class="doc-title">Bank Reconciliation Register</div>
                <div class="doc-meta">Generated {{ now()->format('M j, Y g:i A') }}</div>
            </div>
        </div>

        <h2>Summary</h2>
        <div class="summary">
            <div class="sum-box">
                <div class="lbl">Statement Balance</div>
                <div class="val">{{ $cs }} {{ format_number($kpis['statement_balance'] ?? 0) }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Book Balance</div>
                <div class="val">{{ $cs }} {{ format_number($kpis['book_balance'] ?? 0) }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Matched</div>
                <div class="val">{{ $kpis['matched'] ?? 0 }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Unmatched</div>
                <div class="val">{{ $kpis['unmatched'] ?? 0 }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Adjustments</div>
                <div class="val">{{ $kpis['adjustments'] ?? 0 }}</div>
            </div>
            <div class="sum-box alt">
                <div class="lbl">Difference</div>
                <div class="val">{{ $cs }} {{ format_number($kpis['difference'] ?? 0) }}</div>
            </div>
        </div>

        <h2>Reconciliation Register</h2>
        <table>
            <thead>
                <tr>
                    <th>Bank Account</th>
                    <th>Statement</th>
                    <th>Period End</th>
                    <th class="num">Opening</th>
                    <th class="num">Closing</th>
                    <th class="num">Book</th>
                    <th class="num">Statement Bal.</th>
                    <th class="num">Difference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $reconciliation)
                    <tr>
                        <td>{{ $reconciliation->bankAccount?->code }} &middot; {{ $reconciliation->bankAccount?->name }}</td>
                        <td>{{ $reconciliation->statement_number ?? '—' }}</td>
                        <td>{{ $reconciliation->period_end?->format('M d, Y') ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $reconciliation->opening_balance, 2) }}</td>
                        <td class="num">{{ number_format((float) $reconciliation->closing_balance, 2) }}</td>
                        <td class="num">{{ number_format((float) $reconciliation->book_balance, 2) }}</td>
                        <td class="num">{{ number_format((float) $reconciliation->statement_balance, 2) }}</td>
                        <td class="num">{{ number_format((float) $reconciliation->difference, 2) }}</td>
                        <td>{{ \App\Models\Reconciliation::statusLabel($reconciliation->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3">Total</td>
                    <td class="num">{{ number_format((float) $rows->sum('opening_balance'), 2) }}</td>
                    <td class="num">{{ number_format((float) $rows->sum('closing_balance'), 2) }}</td>
                    <td class="num">{{ number_format((float) $rows->sum('book_balance'), 2) }}</td>
                    <td class="num">{{ number_format((float) $rows->sum('statement_balance'), 2) }}</td>
                    <td class="num">{{ number_format((float) $rows->sum('difference'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="foot">
            {{ $company->name }} &middot; Bank Reconciliation Register &middot; Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
