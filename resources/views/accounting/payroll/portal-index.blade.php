<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',system-ui,sans-serif; background:#eef4f4; color:#111827; }
        .pd-portal-bar { background:linear-gradient(135deg,#17565d,#128F8E); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; color:#fff; }
        .pd-portal-bar .name { font-weight:700; font-size:15px; }
        .pd-portal-bar .sub { font-size:12px; opacity:.8; }
        .pd-portal-bar form button { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); color:#fff; padding:6px 14px; border-radius:8px; font-size:12px; font-family:inherit; cursor:pointer; }
        .pd-portal-wrap { max-width:900px; margin:32px auto; padding:0 24px; }
        .pd-portal-h1 { font-size:24px; font-weight:800; margin-bottom:6px; }
        .pd-portal-sub { font-size:13px; color:#5f7476; margin-bottom:24px; }
        .pd-portal-card { background:#fff; border-radius:16px; border:1px solid #e2ecec; overflow:hidden; }
        .pd-portal-tbl { width:100%; border-collapse:collapse; }
        .pd-portal-tbl th { background:linear-gradient(180deg,#24384f,#182a3e); color:#cdd7e2; font-size:11px; text-transform:uppercase; letter-spacing:.06em; padding:12px 18px; text-align:left; }
        .pd-portal-tbl th.num { text-align:right; }
        .pd-portal-tbl td { padding:14px 18px; border-bottom:1px solid #e2ecec; font-size:13px; }
        .pd-portal-tbl td.num { text-align:right; font-variant-numeric:tabular-nums; }
        .pd-portal-tbl td.bold { font-weight:700; }
        .pd-portal-tbl td.mono { font-family:'Inter',monospace; font-size:12px; }
        .pd-portal-tbl tr:last-child td { border-bottom:none; }
        .pd-portal-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:700; }
        .pd-portal-badge-finalized { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .pd-portal-badge-sent { background:#f0f9ff; color:#128F8E; border:1px solid #b2e5e4; }
        .pd-portal-badge-viewed { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .pd-portal-link { color:#128F8E; text-decoration:none; font-weight:600; font-size:13px; }
        .pd-portal-link:hover { text-decoration:underline; }
        .pd-portal-empty { text-align:center; padding:48px 24px; color:#5f7476; }
        .pd-portal-empty p { font-size:14px; }
    </style>
</head>
<body>
    <div class="pd-portal-bar">
        <div>
            <div class="name">{{ $employee->full_name }}</div>
            <div class="sub">{{ $employee->employee_number }} · {{ $employee->position ?? $employee->department ?? '' }}</div>
        </div>
        <form method="POST" action="{{ route('accounting.payroll.portal.logout') }}">
            @csrf
            <button type="submit">Sign Out</button>
        </form>
    </div>

    <div class="pd-portal-wrap">
        <h1 class="pd-portal-h1">My Payslips</h1>
        <div class="pd-portal-sub">View and download your payslips securely.</div>

        <div class="pd-portal-card">
            @if($payslips->count())
            <table class="pd-portal-tbl">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Payslip #</th>
                        <th>Run</th>
                        <th class="num">Net Pay</th>
                        <th>Status</th>
                        <th style="text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payslips as $p)
                    <tr>
                        <td class="bold">{{ $p->payrollRun?->period_label ?? '—' }}</td>
                        <td class="mono">{{ $p->payslip_number }}</td>
                        <td class="mono">{{ $p->payrollRun?->run_number ?? '—' }}</td>
                        <td class="num bold" style="color:#15803d">{{ $employee->company->base_currency ?? '' }} {{ format_number($p->net_pay) }}</td>
                        <td>
                            <span class="pd-portal-badge pd-portal-badge-{{ $p->status }}">{{ $p->status_label }}</span>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('accounting.payroll.portal.preview', $p) }}" class="pd-portal-link">View →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="pd-portal-empty">
                <p>No payslips available yet.</p>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
