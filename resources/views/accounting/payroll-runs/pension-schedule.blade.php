<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Pension Schedule') }} — {{ $run->run_number }}</title>
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
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
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
            gap: 10px;
            margin-bottom: 25px;
        }
        .info-item .label {
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
        }
        .info-item .value {
            font-size: 12px;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px 14px;
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
            background-color: #fef9ee;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        .no-print {
            text-align: center;
            padding: 20px;
            background: #f3f4f6;
        }
        .no-print button,
        .no-print a {
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .no-print button {
            background: #111827;
            color: #fff;
        }
        .no-print a {
            margin-left: 12px;
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        @media print {
            body {
                background: #fff;
                font-size: 11px;
            }
            .container {
                padding: 15px 20px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">{{ __('Print Schedule') }}</button>
        <a href="{{ route('accounting.payroll-runs.show', $run) }}">{{ __('Back to Run') }}</a>
    </div>

    <div class="container">
        <div class="header">
            <h1>{{ __('Pension Remittance Schedule') }}</h1>
            <p>{{ $run->period_label }}</p>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="label">{{ __('Run Number') }}</div>
                <div class="value">{{ $run->run_number }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('Pay Date') }}</div>
                <div class="value">{{ $run->pay_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('Period') }}</div>
                <div class="value">{{ $run->period_start?->format('M d, Y') ?? '' }} — {{ $run->period_end?->format('M d, Y') ?? '' }}</div>
            </div>
            <div class="info-item">
                <div class="label">{{ __('Generated On') }}</div>
                <div class="value">{{ now()->format('M d, Y H:i') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>{{ __('Employee Name') }}</th>
                    <th class="text-right">{{ __('Employee Pension') }}</th>
                    <th class="text-right">{{ __('Employer Pension') }}</th>
                    <th class="text-right">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalEE = 0;
                    $totalER = 0;
                    $totalGrand = 0;
                @endphp
                @forelse($run->items as $index => $item)
                    @if($item->pension_ee > 0 || $item->pension_er > 0)
                        @php
                            $totalEE += $item->pension_ee;
                            $totalER += $item->pension_er;
                            $totalGrand += ($item->pension_ee + $item->pension_er);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->employee->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($item->pension_ee, 2) }}</td>
                            <td class="text-right">{{ number_format($item->pension_er, 2) }}</td>
                            <td class="text-right">{{ number_format($item->pension_ee + $item->pension_er, 2) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #888;">
                            {{ __('No pension contributions found for this run.') }}
                        </td>
                    </tr>
                @endforelse
                @if($totalGrand > 0)
                    <tr class="totals-row">
                        <td colspan="2">{{ __('Total Pension Remittance') }}</td>
                        <td class="text-right">{{ number_format($totalEE, 2) }}</td>
                        <td class="text-right">{{ number_format($totalER, 2) }}</td>
                        <td class="text-right">{{ number_format($totalGrand, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="footer">
            <p>{{ config('app.name', 'Company') }}</p>
            <p>{{ __('This schedule is generated for remittance purposes.') }}</p>
        </div>
    </div>
</body>
</html>
