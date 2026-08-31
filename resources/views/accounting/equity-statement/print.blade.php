@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $dp = (int) \App\Models\SystemSetting::getValue('currency', 'decimal_places', session('current_company_id'), '2');
    $n = function ($v) use ($dp) {
        $v = (float) $v;
        return $v < 0 ? '('.number_format(abs($v), $dp, '.', ',').')' : number_format($v, $dp, '.', ',');
    };
@endphp

<table class="fs-table">
    <thead>
        <tr>
            <th class="fs-lbl">Code</th>
            <th class="fs-lbl">Account</th>
            <th>Opening ({{ $cs }})</th>
            <th>Movement ({{ $cs }})</th>
            <th>Closing ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $item)
            @php $zero = abs($item['movement']) <= 0 && abs($item['opening']) <= 0 && abs($item['closing']) <= 0; @endphp
            <tr class="@if($zero) fs-zero @endif">
                <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                <td class="fs-name">{{ $item['account']->name }}</td>
                <td class="fs-amt {{ $item['opening'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['opening']) }}</td>
                <td class="fs-amt {{ $item['movement'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['movement']) }}</td>
                <td class="fs-amt {{ $item['closing'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['closing']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="padding:20px 14px;color:var(--faint);text-align:center">No equity accounts found.</td>
            </tr>
        @endforelse
        <tr class="fs-subtotal">
            <td class="fs-amt"><span class="fs-code"></span></td>
            <td>Net Income for the Period</td>
            <td class="fs-amt"></td>
            <td class="fs-amt {{ $net_income < 0 ? 'fs-neg' : '' }}">{{ $n($net_income) }}</td>
            <td class="fs-amt"></td>
        </tr>
        <tr class="fs-total">
            <td class="fs-amt"><span class="fs-code"></span></td>
            <td>Total Equity</td>
            <td class="fs-amt">{{ $n($total_opening) }}</td>
            <td class="fs-amt {{ ($total_closing - $total_opening) < 0 ? 'fs-neg' : '' }}">{{ $n($total_closing - $total_opening) }}</td>
            <td class="fs-amt">{{ $n($total_closing) }}</td>
        </tr>
    </tbody>
</table>
