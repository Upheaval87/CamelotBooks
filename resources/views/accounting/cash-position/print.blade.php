<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cash Position - {{ $f['date_from'] }} to {{ $f['date_to'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Arial, sans-serif; color: #0B2A2D; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { padding: 40px 48px; }
        .head { background: linear-gradient(135deg, #17565D, #0C3539 60%, #0A2E32); border-radius: 14px; padding: 28px 32px; color: #fff; display: flex; align-items: center; justify-content: space-between; margin-bottom: 26px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo { width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #0C7E7D, #107C7B); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: #EAFFFF; }
        .brand-name { font-weight: 700; font-size: 15px; letter-spacing: .02em; }
        .doc { text-align: right; }
        .doc-title { font-size: 22px; font-weight: 800; letter-spacing: -0.01em; }
        .doc-meta { font-size: 11px; color: rgba(226,244,244,.75); margin-top: 4px; }
        h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #52696B; margin: 24px 0 10px; }
        .summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
        .sum-box { border: 1px solid #DCEAEA; border-radius: 10px; padding: 14px 16px; }
        .sum-box .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #52696B; margin-bottom: 6px; }
        .sum-box .val { font-size: 15px; font-weight: 700; }
        .sum-box.alt { background: linear-gradient(135deg, #0C7E7D, #107C7B); border: none; color: #fff; }
        .sum-box.alt .lbl { color: rgba(226,244,244,.8); }
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; text-align: left; padding: 9px 10px; background: #11454B; color: #E2F4F4; }
        th.num, td.num { text-align: right; font-variant-numeric: tabular-nums; }
        td { font-size: 12px; padding: 9px 10px; border-bottom: 1px solid #E2ECEC; }
        tr.total td { font-weight: 700; background: #F0F6F6; border-bottom: none; }
        .foot { margin-top: 28px; padding-top: 14px; border-top: 1px solid #DCEAEA; font-size: 10px; color: #5F7A7C; text-align: center; }
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
                <div class="doc-title">Cash Position</div>
                <div class="doc-meta">{{ $f['date_from'] }} to {{ $f['date_to'] }} &middot; Generated {{ now()->format('M j, Y g:i A') }}</div>
            </div>
        </div>

        <h2>Summary</h2>
        <div class="summary">
            <div class="sum-box">
                <div class="lbl">Period Opening</div>
                <div class="val">{{ format_number($movement->sum('opening')) }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Receipts</div>
                <div class="val">{{ format_number($movement->sum('receipts')) }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Payments</div>
                <div class="val">{{ format_number($movement->sum('payments')) }}</div>
            </div>
            <div class="sum-box">
                <div class="lbl">Net Change</div>
                <div class="val">{{ format_number($movement->sum('receipts') - $movement->sum('payments')) }}</div>
            </div>
            <div class="sum-box alt">
                <div class="lbl">Closing Balance</div>
                <div class="val">{{ format_number($movement->sum('closing')) }}</div>
            </div>
        </div>

        <h2>Cash Position by Account</h2>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Currency</th>
                    <th class="num">Opening</th>
                    <th class="num">Receipts</th>
                    <th class="num">Payments</th>
                    <th class="num">Transfers In</th>
                    <th class="num">Transfers Out</th>
                    <th class="num">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movement as $row)
                    <tr>
                        <td>{{ $row['code'] }} &middot; {{ $row['name'] }}</td>
                        <td>{{ $row['currency'] }}</td>
                        <td class="num">{{ format_number($row['opening']) }}</td>
                        <td class="num">{{ format_number($row['receipts']) }}</td>
                        <td class="num">{{ format_number($row['payments']) }}</td>
                        <td class="num">{{ format_number($row['transfers_in']) }}</td>
                        <td class="num">{{ format_number($row['transfers_out']) }}</td>
                        <td class="num">{{ format_number($row['closing']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="2">Total</td>
                    <td class="num">{{ format_number($movement->sum('opening')) }}</td>
                    <td class="num">{{ format_number($movement->sum('receipts')) }}</td>
                    <td class="num">{{ format_number($movement->sum('payments')) }}</td>
                    <td class="num">{{ format_number($movement->sum('transfers_in')) }}</td>
                    <td class="num">{{ format_number($movement->sum('transfers_out')) }}</td>
                    <td class="num">{{ format_number($movement->sum('closing')) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="foot">
            {{ $company->name }} &middot; Cash Position report &middot; {{ $f['date_from'] }} to {{ $f['date_to'] }}
        </div>
    </div>
</body>
</html>
