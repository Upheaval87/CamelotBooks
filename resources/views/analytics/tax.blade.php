<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'tax';
            $title = 'Tax';
            $subtitle = 'VAT payable/receivable and year-to-date output vs input for ' . $period['label'] . '.';
            $k = $data['kpis'] ?? [];
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.tax') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                        <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.tax') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">VAT Payable</div><div class="v dn">@money($k['vat_payable']['value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">VAT Receivable</div><div class="v up">@money($k['vat_receivable']['value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Net Due</div><div class="v {{ ($k['net_due']['value'] ?? 0) >= 0 ? 'dn' : 'up' }}">@money($k['net_due']['value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Net YTD (Output − Input)</div><div class="v {{ ($k['net_ytd']['value'] ?? 0) >= 0 ? 'dn' : 'up' }}">@money($k['net_ytd']['value'] ?? 0)</div></div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Year to Date</h2>
                <table class="an-tbl">
                    <tbody>
                        <tr><td>Output VAT (on sales)</td><td class="r">@money($k['output_ytd']['value'] ?? 0)</td></tr>
                        <tr><td>Input VAT (on purchases)</td><td class="r">@money($k['input_ytd']['value'] ?? 0)</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="an-card">
                <div class="an-empty-cell an-muted-msg">Net due reflects the general-ledger balance of your VAT control accounts as of {{ $period['as_of'] }}. Variances against computed output/input arise from journal adjustments and reversible entries.</div>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Monthly Output vs Input</h2>
            @php
                $labels = $data['monthly_labels'] ?? [];
                $output = $data['monthly_output'] ?? [];
                $input = $data['monthly_input'] ?? [];
                $maxV = max(array_merge([1], array_map('abs', array_merge($output, $input))));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: out @money($output[$i] ?? 0) · in @money($input[$i] ?? 0)"></span>
                            <div class="an-bar-cols an-bar-cols--pair">
                                <i class="an-bar-b net" style="height:{{ ((abs($output[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                                <i class="an-bar-b exp" style="height:{{ ((abs($input[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No VAT activity in this period.</div>
            @endif
        </div>
    </div>
</x-app-layout>