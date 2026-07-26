<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; font-size: 18px; margin-bottom: 5px;">{{ $company->name }}</h1>
    <h2 style="text-align: center; font-size: 14px; color: #555; margin-bottom: 20px;">Statement of Changes in Equity</h2>
    <p style="text-align: center; font-size: 12px; color: #777; margin-bottom: 20px;">
        For the period {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
    </p>

    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
        <thead>
            <tr style="background: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                <th style="text-align: left; padding: 8px;">Account</th>
                <th style="text-align: right; padding: 8px;">Opening</th>
                <th style="text-align: right; padding: 8px;">Movement</th>
                <th style="text-align: right; padding: 8px;">Closing</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $item)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 6px 8px;">{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                    <td style="text-align: right; padding: 6px 8px;">{{ format_money($item['opening']) }}</td>
                    <td style="text-align: right; padding: 6px 8px;">{{ $item['movement'] >= 0 ? '+' : '' }}{{ format_money($item['movement']) }}</td>
                    <td style="text-align: right; padding: 6px 8px; font-weight: bold;">{{ format_money($item['closing']) }}</td>
                </tr>
            @endforeach
            <tr style="background: #eff6ff; border-bottom: 1px solid #d1d5db;">
                <td style="padding: 6px 8px; font-weight: bold;">Net Income for Period</td>
                <td style="padding: 6px 8px;"></td>
                <td style="text-align: right; padding: 6px 8px; font-weight: bold;">{{ $net_income >= 0 ? '+' : '' }}{{ format_money($net_income) }}</td>
                <td style="padding: 6px 8px;"></td>
            </tr>
            <tr style="background: #f3f4f6; font-weight: bold; border-top: 2px solid #d1d5db;">
                <td style="padding: 8px;">Total Equity</td>
                <td style="text-align: right; padding: 8px;">{{ format_money($total_opening) }}</td>
                <td style="text-align: right; padding: 8px;">{{ ($total_closing - $total_opening) >= 0 ? '+' : '' }}{{ format_money($total_closing - $total_opening) }}</td>
                <td style="text-align: right; padding: 8px;">{{ format_money($total_closing) }}</td>
            </tr>
        </tbody>
    </table>

    <p style="font-size: 10px; color: #999; margin-top: 20px; text-align: center;">
        Generated {{ now()->format('M d, Y H:i') }}
    </p>
</div>
