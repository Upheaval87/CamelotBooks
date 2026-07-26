<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payslips') }} — {{ $run->run_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Figtree', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #fff;
        }
        .payslip {
            page-break-after: always;
            padding: 40px;
            border-bottom: 2px dashed #ccc;
        }
        .payslip:last-child {
            page-break-after: auto;
            border-bottom: none;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #555;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        .info-item {
            font-size: 12px;
        }
        .info-item .label {
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
        }
        .info-item .value {
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            color: #555;
        }
        td.text-right, th.text-right {
            text-align: right;
        }
        .totals-row td {
            font-weight: 700;
            border-top: 2px solid #1a1a1a;
            border-bottom: none;
        }
        .net-pay-row td {
            font-size: 14px;
            font-weight: 700;
            color: #166534;
            background-color: #f0fdf4;
            border-top: 2px solid #16a34a;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        @media print {
            body {
                background: #fff;
                font-size: 11px;
            }
            .payslip {
                padding: 20px 30px;
                page-break-after: always;
                border-bottom: none;
            }
            .payslip:last-child {
                page-break-after: auto;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 20px; background: #f3f4f6;">
        <button onclick="window.print()" style="padding: 10px 24px; background: #111827; color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
            {{ __('Print Payslips') }}
        </button>
        <a href="{{ route('accounting.payroll-runs.show', $run) }}" style="margin-left: 12px; padding: 10px 24px; background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; text-decoration: none; display: inline-block;">
            {{ __('Back to Run') }}
        </a>
    </div>

    @foreach($run->items as $item)
        @php
            $payslipData = $item->payslip_data ?? [];
            $employee = $item->employee;
        @endphp
        <div class="payslip">
            <div class="header">
                <h1>{{ __('Payslip') }}</h1>
                <p>{{ $run->period_label }} — {{ $employee->name ?? 'Employee' }}</p>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">{{ __('Employee Name') }}</div>
                    <div class="value">{{ $employee->name ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">{{ __('Employee ID') }}</div>
                    <div class="value">{{ $employee->employee_id ?? $employee->id ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">{{ __('Pay Date') }}</div>
                    <div class="value">{{ $run->pay_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">{{ __('Period') }}</div>
                    <div class="value">{{ $run->period_start?->format('M d, Y') ?? '' }} — {{ $run->period_end?->format('M d, Y') ?? '' }}</div>
                </div>
                @if(isset($payslipData['department']))
                    <div class="info-item">
                        <div class="label">{{ __('Department') }}</div>
                        <div class="value">{{ $payslipData['department'] }}</div>
                    </div>
                @endif
                @if(isset($payslipData['designation']))
                    <div class="info-item">
                        <div class="label">{{ __('Designation') }}</div>
                        <div class="value">{{ $payslipData['designation'] }}</div>
                    </div>
                @endif
            </div>

            <h3 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #555;">{{ __('Earnings') }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th class="text-right">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('Basic Pay') }}</td>
                        <td class="text-right">{{ number_format($item->basic_pay, 2) }}</td>
                    </tr>
                    @if($item->allowances > 0)
                        <tr>
                            <td>{{ __('Allowances') }}</td>
                            <td class="text-right">{{ number_format($item->allowances, 2) }}</td>
                        </tr>
                    @endif
                    @if(isset($payslipData['earnings']) && is_array($payslipData['earnings']))
                        @foreach($payslipData['earnings'] as $earning)
                            <tr>
                                <td>{{ $earning['label'] ?? '—' }}</td>
                                <td class="text-right">{{ number_format($earning['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr class="totals-row">
                        <td>{{ __('Gross Pay') }}</td>
                        <td class="text-right">{{ number_format($item->gross_pay, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #555;">{{ __('Deductions') }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th class="text-right">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($item->paye > 0)
                        <tr>
                            <td>{{ __('PAYE') }}</td>
                            <td class="text-right">{{ number_format($item->paye, 2) }}</td>
                        </tr>
                    @endif
                    @if($item->pension_ee > 0)
                        <tr>
                            <td>{{ __('Pension (Employee)') }}</td>
                            <td class="text-right">{{ number_format($item->pension_ee, 2) }}</td>
                        </tr>
                    @endif
                    @if($item->other_deductions > 0)
                        <tr>
                            <td>{{ __('Other Deductions') }}</td>
                            <td class="text-right">{{ number_format($item->other_deductions, 2) }}</td>
                        </tr>
                    @endif
                    @if(isset($payslipData['deductions']) && is_array($payslipData['deductions']))
                        @foreach($payslipData['deductions'] as $deduction)
                            <tr>
                                <td>{{ $deduction['label'] ?? '—' }}</td>
                                <td class="text-right">{{ number_format($deduction['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr class="totals-row">
                        <td>{{ __('Total Deductions') }}</td>
                        <td class="text-right">{{ number_format($item->paye + $item->pension_ee + $item->other_deductions, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <tbody>
                    <tr class="net-pay-row">
                        <td>{{ __('Net Pay') }}</td>
                        <td class="text-right">{{ number_format($item->net_pay, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            @if(isset($payslipData['employer_contributions']) && is_array($payslipData['employer_contributions']))
                <h3 style="font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #555; margin-top: 20px;">{{ __('Employer Contributions') }}</h3>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Description') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payslipData['employer_contributions'] as $contribution)
                            <tr>
                                <td>{{ $contribution['label'] ?? '—' }}</td>
                                <td class="text-right">{{ number_format($contribution['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="footer">
                <p>{{ config('app.name', 'Company') }}</p>
                <p>{{ __('This is a computer-generated payslip and does not require a signature.') }}</p>
            </div>
        </div>
    @endforeach
</body>
</html>
