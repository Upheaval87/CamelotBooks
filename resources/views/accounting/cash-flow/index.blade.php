<x-app-layout>
    @php
        $cs = $currencySymbol ?? '$';
        $dp = $dp ?? 2;
        $periodLabel = __('For the period') . ' ' . \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
        $currentPeriodLabel = \Carbon\Carbon::parse($dateFrom)->format('M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M Y');
        $drillParams = http_build_query(array_filter([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
        ]));
    @endphp

    <div class="fr-wrap">
        {{-- branded header — hidden on screen, shown in @media print --}}
        @include('accounting.statement-branded-header', [
            'company' => $company,
            'title' => 'Cash Flow Statement',
            'periodLabel' => $periodLabel,
            'currency' => $currency,
            'cs' => $cs,
            'basis' => 'Accrual',
            'preparedBy' => $preparedBy ?? '—',
        ])
        <div class="fr-head">
            <div>
                <h1>{{ __('Cash Flow Statement') }}</h1>
                <div class="fr-sub">{{ $currentPeriodLabel }} · {{ $cs }}</div>
            </div>
            <div class="fr-actions">
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">Print</button>
                <a href="{{ route('accounting.cash-flow.export-pdf', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">PDF</a>
                <a href="{{ route('accounting.cash-flow.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">Excel</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.cash-flow.index') }}" class="fr-filters">
            <div class="fr-f">
                <label for="date_from">{{ __('Date From') }}</label>
                <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="fr-f">
                <label for="date_to">{{ __('Date To') }}</label>
                <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="fr-f">
                <label for="branch_id">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (int)($branchId ?? 0) === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="fr-btn fr-btn-cta fr-btn-sm">Generate</button>
                <a href="{{ route('accounting.cash-flow.index') }}" class="fr-btn fr-btn-ghost fr-btn-sm">Clear</a>
            </div>
        </form>

        @if($mismatch)
            <div class="fr-banner err">
                <div class="fr-banner-ic">✗</div>
                <span>Ending cash ({{ format_number($ending_cash) }}) does not match actual bank balances ({{ format_number($actual_ending_cash) }}). Difference: {{ format_number(abs($mismatch)) }}</span>
            </div>
        @endif

        <div class="fr-kpis">
            <div class="fr-kpi hero"><div class="fr-kpi-l">Net Change</div><div class="fr-kpi-v {{ $net_change < 0 ? 'red' : '' }}">{{ format_number($net_change) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Operating</div><div class="fr-kpi-v {{ $operating_total < 0 ? 'red' : 'green' }}">{{ format_number($operating_total) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Investing</div><div class="fr-kpi-v {{ $investing_total < 0 ? 'red' : 'green' }}">{{ format_number($investing_total) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Financing</div><div class="fr-kpi-v {{ $financing_total < 0 ? 'red' : 'green' }}">{{ format_number($financing_total) }}</div></div>
        </div>

        <div class="fr-card">
            <div class="fr-card-head">
                <h2>Cash Flow Statement</h2>
                <div style="margin-left:auto;font-size:.75rem;color:var(--muted,#5f7476)">{{ $cs }}</div>
            </div>
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th style="width:60%">Description</th>
                            <th class="r" style="width:20%">Inflow</th>
                            <th class="r" style="width:20%">Outflow</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- OPERATING --}}
                        <tr class="fr-section-header"><td colspan="3">Operating Activities</td></tr>
                        <tr>
                            <td class="fr-tl" style="padding-left:1.5rem">Net Income</td>
                            <td class="r">{{ $net_income > 0 ? format_number($net_income) : '–' }}</td>
                            <td class="r">{{ $net_income < 0 ? format_number(abs($net_income)) : '–' }}</td>
                        </tr>
                        @foreach($non_cash_expenses['items'] ?? [] as $item)
                            @if(abs($item['amount']) > 0)
                                <tr>
                                    <td class="fr-tl" style="padding-left:1.5rem">
                                        <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a>
                                    </td>
                                    <td class="r">{{ $item['amount'] > 0 ? format_number($item['amount']) : '–' }}</td>
                                    <td class="r">{{ $item['amount'] < 0 ? format_number(abs($item['amount'])) : '–' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        @foreach($sections['operating'] as $item)
                            @if(abs($item['cash_effect']) > 0)
                                <tr>
                                    <td class="fr-tl" style="padding-left:1.5rem">
                                        <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ ($item['change'] > 0 ? 'Increase in ' : 'Decrease in ') . $item['account']->name }}</a>
                                    </td>
                                    <td class="r">{{ $item['cash_effect'] > 0 ? format_number($item['cash_effect']) : '–' }}</td>
                                    <td class="r">{{ $item['cash_effect'] < 0 ? format_number(abs($item['cash_effect'])) : '–' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr class="fr-subtotal">
                            <td class="fr-tn">Net Cash from Operating</td>
                            <td class="r fr-tn">{{ $operating_total > 0 ? format_number($operating_total) : '–' }}</td>
                            <td class="r fr-tn">{{ $operating_total < 0 ? format_number(abs($operating_total)) : '–' }}</td>
                        </tr>

                        {{-- INVESTING --}}
                        <tr class="fr-section-header"><td colspan="3">Investing Activities</td></tr>
                        @forelse($sections['investing'] as $item)
                            @if(abs($item['cash_effect']) > 0)
                                <tr>
                                    <td class="fr-tl" style="padding-left:1.5rem">
                                        <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ ($item['change'] > 0 ? 'Increase in ' : 'Decrease in ') . $item['account']->name }}</a>
                                    </td>
                                    <td class="r">{{ $item['cash_effect'] > 0 ? format_number($item['cash_effect']) : '–' }}</td>
                                    <td class="r">{{ $item['cash_effect'] < 0 ? format_number(abs($item['cash_effect'])) : '–' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr><td class="fr-tl" style="padding-left:1.5rem;color:var(--faint,#8aa5a7)">No investing activities</td><td class="r">–</td><td class="r">–</td></tr>
                        @endforelse
                        <tr class="fr-subtotal">
                            <td class="fr-tn">Net Cash from Investing</td>
                            <td class="r fr-tn">{{ $investing_total > 0 ? format_number($investing_total) : '–' }}</td>
                            <td class="r fr-tn">{{ $investing_total < 0 ? format_number(abs($investing_total)) : '–' }}</td>
                        </tr>

                        {{-- FINANCING --}}
                        <tr class="fr-section-header"><td colspan="3">Financing Activities</td></tr>
                        @forelse($sections['financing'] as $item)
                            @if(abs($item['cash_effect']) > 0)
                                <tr>
                                    <td class="fr-tl" style="padding-left:1.5rem">
                                        <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ ($item['change'] > 0 ? 'Increase in ' : 'Decrease in ') . $item['account']->name }}</a>
                                    </td>
                                    <td class="r">{{ $item['cash_effect'] > 0 ? format_number($item['cash_effect']) : '–' }}</td>
                                    <td class="r">{{ $item['cash_effect'] < 0 ? format_number(abs($item['cash_effect'])) : '–' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr><td class="fr-tl" style="padding-left:1.5rem;color:var(--faint,#8aa5a7)">No financing activities</td><td class="r">–</td><td class="r">–</td></tr>
                        @endforelse
                        <tr class="fr-subtotal">
                            <td class="fr-tn">Net Cash from Financing</td>
                            <td class="r fr-tn">{{ $financing_total > 0 ? format_number($financing_total) : '–' }}</td>
                            <td class="r fr-tn">{{ $financing_total < 0 ? format_number(abs($financing_total)) : '–' }}</td>
                        </tr>

                        {{-- SUMMARY --}}
                        <tr class="fr-section-header"><td colspan="3">Summary</td></tr>
                        <tr><td class="fr-tl">Net Change in Cash</td><td class="r" colspan="2" style="font-weight:800">{{ format_number($net_change) }}</td></tr>
                        <tr><td class="fr-tl">Beginning Cash Balance</td><td class="r" colspan="2">{{ format_number($beginning_cash) }}</td></tr>
                        <tr class="fr-net-total"><td class="fr-tn" style="font-size:.9375rem">Ending Cash Balance</td><td class="r fr-tn" colspan="2" style="font-size:.9375rem">{{ format_number($ending_cash) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fr-actionbar">
            <a href="{{ route('accounting.cash-flow.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost">Export CSV</a>
            <a href="{{ route('accounting.cash-flow.export-pdf', request()->query()) }}" class="fr-btn fr-btn-cta">Print / PDF</a>
        </div>

        {{-- branded footer — hidden on screen, shown in @media print --}}
        @php
            $_branchLine = $branchId ? ($branches->firstWhere('id', (int) $branchId)->name ?? null) : null;
            $_orgLine = trim(implode(' · ', array_filter([$_branchLine, $company->tax_id ? 'TPIN '.$company->tax_id : null])));
        @endphp
        <footer class="co-foot">
            <span>{{ $company->name ?? 'Company' }}{{ $_orgLine ? ' · '.$_orgLine : '' }}</span>
            <span class="co-foot-pg">Cash Flow Statement · <span class="co-pageno"></span></span>
        </footer>
    </div>

    @push('scripts')
    <script>
    document.querySelectorAll('.co-pageno').forEach(function (el) {
        el.textContent = 'Page 1 of 1';
    });
    </script>
</x-app-layout>
