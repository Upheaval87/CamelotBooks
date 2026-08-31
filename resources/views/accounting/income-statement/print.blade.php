@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $dp = (int) \App\Models\SystemSetting::getValue('currency', 'decimal_places', session('current_company_id'), '2');
    $n = function ($v) use ($dp) {
        $v = (float) $v;
        return $v < 0 ? '('.number_format(abs($v), $dp, '.', ',').')' : number_format($v, $dp, '.', ',');
    };
    // section order + label for the classified layout (matches available account sub_types)
    $sectionOrder = ['revenue' => 'Revenue', 'other_income' => 'Other Income'];
    $periodTotals = ['revenue' => 0, 'other_income' => 0];
@endphp

<table class="fs-table">
    <thead>
        <tr>
            <th class="fs-lbl">Code</th>
            <th class="fs-lbl">Account</th>
            <th>Current Period ({{ $cs }})</th>
            <th>Year to Date ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sectionOrder as $subType => $label)
            @if(!empty($groups['income'][$subType]))
                <tr class="fs-section">
                    <td class="fs-stub" colspan="4">{{ $label }}</td>
                </tr>
                @foreach($groups['income'][$subType] as $item)
                    @php
                        $net = max(0, $item['net']);
                        $ytd = max(0, $ytd_nets[$item['account']->id] ?? 0);
                        $periodTotals[$subType] += $net;
                        $zero = $net <= 0 && $ytd <= 0;
                    @endphp
                    <tr class="@if($zero) fs-zero @endif">
                        <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                        <td class="fs-name">{{ $item['account']->name }}</td>
                        <td class="fs-amt">{{ $n($net) }}</td>
                        <td class="fs-amt">{{ $n($ytd) }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Total Revenue</td>
            <td class="fs-amt">{{ $n($total_income) }}</td>
            <td class="fs-amt">{{ $n($ytd_income) }}</td>
        </tr>

        <tr class="fs-section">
            <td class="fs-stub" colspan="4">Expenses</td>
        </tr>
        @foreach($groups['expense'] as $subType => $items)
            @foreach($items as $item)
                @php
                    $net = max(0, $item['net']);
                    $ytd = max(0, $ytd_nets[$item['account']->id] ?? 0);
                    $zero = $net <= 0 && $ytd <= 0;
                @endphp
                <tr class="@if($zero) fs-zero @endif">
                    <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                    <td class="fs-name">{{ $item['account']->name }}</td>
                    <td class="fs-amt">{{ $n($net) }}</td>
                    <td class="fs-amt">{{ $n($ytd) }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Total Expenses</td>
            <td class="fs-amt">{{ $n($total_expenses) }}</td>
            <td class="fs-amt">{{ $n($ytd_expenses) }}</td>
        </tr>

        <tr class="fs-total">
            <td class="fs-stub" colspan="2">{{ $net_income >= 0 ? 'Net Income' : 'Net Loss' }}</td>
            <td class="fs-amt">{{ $n($net_income) }}</td>
            <td class="fs-amt">{{ $n($ytd_net) }}</td>
        </tr>
    </tbody>
</table>
