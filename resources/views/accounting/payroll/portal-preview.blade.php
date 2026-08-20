<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip {{ $payslip->payslip_number }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',system-ui,sans-serif; background:#eef4f4; color:#111827; }
        .pd-portal-bar { background:linear-gradient(135deg,#17565d,#128F8E); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; color:#fff; }
        .pd-portal-bar a { color:#fff; text-decoration:none; font-size:13px; opacity:.85; }
        .pd-portal-bar a:hover { opacity:1; }
        .pd-paper-wrap { max-width:800px; margin:32px auto; padding:0 24px; }
        .pd-paper { background:#fff; border-radius:20px; border:1px solid #e2ecec; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.04); }
        .pd-paper-head { background:linear-gradient(135deg,#17565d,#128F8E); padding:32px; color:#fff; }
        .pd-paper-head h1 { font-size:22px; font-weight:800; }
        .pd-paper-head .ref { font-size:12px; opacity:.7; margin-top:4px; }
        .pd-paper-body { padding:32px; }
        .pd-paper-emp { display:flex; gap:16px; align-items:center; padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid #e2ecec; }
        .pd-paper-emp .ava { width:48px; height:48px; border-radius:50%; background:rgba(17,69,75,.08); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; color:#17565d; }
        .pd-paper-emp .name { font-weight:800; font-size:16px; }
        .pd-paper-emp .meta { font-size:12px; color:#5f7476; margin-top:2px; }
        .pd-paper-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
        .pd-paper-section h4 { font-size:12px; text-transform:uppercase; letter-spacing:.06em; color:#5f7476; margin-bottom:10px; }
        .pd-paper-tbl { width:100%; border-collapse:collapse; }
        .pd-paper-tbl th { background:#f4f8f8; padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; color:#5f7476; }
        .pd-paper-tbl th.num { text-align:right; }
        .pd-paper-tbl td { padding:8px 12px; border-bottom:1px solid #f0f0f0; font-size:13px; }
        .pd-paper-tbl td.b { font-weight:700; }
        .pd-paper-tbl td.num { text-align:right; font-variant-numeric:tabular-nums; }
        .pd-paper-tbl tr.tot { background:#f4f8f8; font-weight:800; }
        .pd-paper-net { background:linear-gradient(135deg,#17565d,#128F8E); border-radius:14px; padding:24px; text-align:center; color:#fff; margin:24px 0; }
        .pd-paper-net .l { font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:.7; }
        .pd-paper-net .v { font-size:28px; font-weight:800; margin:4px 0; }
        .pd-paper-net .s { font-size:12px; opacity:.7; }
        .pd-paper-footer { border-top:1px solid #e2ecec; padding:16px 32px; font-size:11px; color:#5f7476; text-align:center; }
    </style>
</head>
<body>
    @php
        $employee = $payslip->employee;
        $company = $payslip->company;
        $run = $payslip->payrollRun;
        $empInitials = strtoupper(substr($employee?->first_name ?? '', 0, 1) . substr($employee?->last_name ?? '', 0, 1));
        $currency = $company->base_currency ?? '';
        $grossPay = $payslip->gross_pay ?? 0;
        $totalDeductions = $payslip->total_deductions ?? 0;
        $netPay = $payslip->net_pay ?? 0;
    @endphp

    <div class="pd-portal-bar">
        <a href="{{ route('accounting.payroll.portal.index') }}">← Back to My Payslips</a>
        <a href="{{ route('accounting.payroll.portal.logout') }}">Sign Out</a>
    </div>

    <div class="pd-paper-wrap">
        <div class="pd-paper">
            <div class="pd-paper-head">
                <h1>{{ $company->name ?? 'Company' }}</h1>
                <div class="ref">{{ $payslip->payslip_number }} · {{ $run->period_label ?? '' }}</div>
            </div>
            <div class="pd-paper-body">
                <div class="pd-paper-emp">
                    <div class="ava">{{ $empInitials }}</div>
                    <div>
                        <div class="name">{{ $employee->full_name ?? '—' }}</div>
                        <div class="meta">{{ $employee->position ?? '' }} · {{ $employee->department ?? '' }} · {{ $employee->employee_number ?? '' }}</div>
                    </div>
                </div>

                <div class="pd-paper-grid">
                    <div class="pd-paper-section">
                        <h4>Earnings</h4>
                        <table class="pd-paper-tbl">
                            <thead><tr><th>Item</th><th>Basis</th><th class="num">Amount</th></tr></thead>
                            <tbody>
                                @foreach($payslip->earnings ?? [] as $e)
                                <tr>
                                    <td class="b">{{ $e['item'] ?? '—' }}</td>
                                    <td>{{ $e['basis'] ?? '—' }}</td>
                                    <td class="num">{{ $currency }} {{ format_number($e['amount'] ?? 0) }}</td>
                                </tr>
                                @endforeach
                                <tr class="tot">
                                    <td colspan="2">Gross Pay</td>
                                    <td class="num">{{ $currency }} {{ format_number($grossPay) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pd-paper-section">
                        <h4>Deductions</h4>
                        <table class="pd-paper-tbl">
                            <thead><tr><th>Item</th><th>Basis</th><th class="num">Amount</th></tr></thead>
                            <tbody>
                                @foreach($payslip->deductions ?? [] as $d)
                                <tr>
                                    <td class="b">{{ $d['item'] ?? '—' }}</td>
                                    <td>{{ $d['basis'] ?? '—' }}</td>
                                    <td class="num">{{ $currency }} {{ format_number($d['amount'] ?? 0) }}</td>
                                </tr>
                                @endforeach
                                <tr class="tot">
                                    <td colspan="2">Total Deductions</td>
                                    <td class="num">{{ $currency }} {{ format_number($totalDeductions) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pd-paper-net">
                    <div class="l">Net Pay</div>
                    <div class="v">{{ $currency }} {{ format_number($netPay) }}</div>
                    <div class="s">Paid via {{ $employee->bank_name ?? '—' }} {{ $employee->bank_account_number ? '····' . substr($employee->bank_account_number, -4) : '' }} on {{ $run->pay_date?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
            <div class="pd-paper-footer">
                Confidential — System-generated payslip. For questions, contact your payroll administrator.
            </div>
        </div>
    </div>
</body>
</html>
