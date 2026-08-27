<x-app-layout>
    @php
        $cs = $currencySymbol ?? '$';
        $hasPrev = !empty($prevStatement);
        $currentAssets = $total_assets;
        $prevAssets = $hasPrev ? ($prevStatement['total_assets'] ?? 0) : null;
        $currentLiabilities = $total_liabilities;
        $prevLiabilities = $hasPrev ? ($prevStatement['total_liabilities'] ?? 0) : null;
        $currentEquity = $total_equity;
        $prevEquity = $hasPrev ? ($prevStatement['total_equity'] ?? 0) : null;
        $workingCapital = $currentAssets - $currentLiabilities;
        $currentRatio = $currentLiabilities != 0 ? $currentAssets / $currentLiabilities : 0;
        $debtToEquity = $currentEquity != 0 ? $currentLiabilities / $currentEquity : 0;
        $equityRatio = $currentAssets != 0 ? ($currentEquity / $currentAssets) * 100 : 0;
        $drillParams = http_build_query(array_filter([
            'date_to' => $asOfDate,
            'branch_id' => $branchId,
        ]));
    @endphp

    <div class="fr-wrap" x-data="frExpandAll()">
        <div class="fr-head">
            <div>
                <h1>{{ __('Statement of Financial Position') }}</h1>
                <div class="fr-sub">As at {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }} · {{ $cs }} · comparative</div>
            </div>
            <div class="fr-actions">
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" @click="expandAll()">Expand All</button>
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" @click="collapseAll()">Collapse All</button>
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">Print</button>
                <a href="{{ route('accounting.balance-sheet.export-pdf', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">PDF</a>
                <a href="{{ route('accounting.balance-sheet.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">Excel</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.balance-sheet.index') }}" class="fr-filters">
            <div class="fr-f">
                <label for="as_of_date">{{ __('As Of Date') }}</label>
                <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}">
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
                <a href="{{ route('accounting.balance-sheet.index') }}" class="fr-btn fr-btn-ghost fr-btn-sm">Clear</a>
            </div>
        </form>

        @if($balanced)
            <div class="fr-banner ok">
                <div class="fr-banner-ic">✓</div>
                <span>BALANCED — Total Assets = Total Liabilities + Equity</span>
            </div>
        @else
            <div class="fr-banner err">
                <div class="fr-banner-ic">✗</div>
                <span>UNBALANCED — Out of balance by {{ $cs }}{{ format_number(abs($total_assets - ($total_liabilities + $total_equity))) }}</span>
            </div>
        @endif

        <div class="fr-kpis">
            <div class="fr-kpi hero"><div class="fr-kpi-l">Total Assets</div><div class="fr-kpi-v">{{ format_number($total_assets) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Total Liabilities</div><div class="fr-kpi-v">{{ format_number($total_liabilities) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Total Equity</div><div class="fr-kpi-v">{{ format_number($total_equity) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Working Capital</div><div class="fr-kpi-v {{ $workingCapital < 0 ? 'red' : '' }}">{{ format_number($workingCapital) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Current Ratio</div><div class="fr-kpi-v">{{ number_format($currentRatio, 2) }}x</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Debt-to-Equity</div><div class="fr-kpi-v">{{ number_format($debtToEquity, 2) }}x</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Equity Ratio</div><div class="fr-kpi-v">{{ number_format($equityRatio, 1) }}%</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Net Assets</div><div class="fr-kpi-v">{{ format_number($currentEquity) }}</div></div>
        </div>

        <div class="fr-card">
            <div class="fr-card-head">
                <h2>Statement of Financial Position</h2>
                <div style="margin-left:auto;font-size:.75rem;color:var(--muted,#5f7476)">{{ $cs }} · Current vs {{ \Carbon\Carbon::parse($prevAsOf)->format('Y') }}</div>
            </div>
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th style="width:40%">Description</th>
                            <th class="r" style="width:15%">Current</th>
                            <th class="r" style="width:15%">Previous</th>
                            <th class="r" style="width:15%">Variance</th>
                            <th class="r" style="width:15%">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $varianceDisplay = function($cur, $prev) {
                                if ($prev === null) return [null, null];
                                $var = $cur - $prev;
                                $pct = $prev != 0 ? ($var / abs($prev)) * 100 : null;
                                return [$var, $pct];
                            };
                        @endphp

                        {{-- ASSETS --}}
                        <tr class="fr-section-header"><td colspan="5">Assets</td></tr>
                        @foreach($groups['asset'] as $subType => $items)
                            @if(!empty($items))
                                <tr class="fr-subsection-header" @click="toggleSection('a-{{ $subType }}')">
                                    <td colspan="5"><span class="fr-expand-icon" :class="isExpanded('a-{{ $subType }}') ? 'open' : ''">▸</span> {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                </tr>
                                @foreach($items as $item)
                                    @php
                                        $cur = $item['balance'];
                                        $prevBal = null;
                                        if ($hasPrev && isset($prevStatement['groups']['asset'][$subType])) {
                                            foreach ($prevStatement['groups']['asset'][$subType] as $pb) {
                                                if ($pb['account']->id === $item['account']->id) { $prevBal = $pb['balance']; break; }
                                            }
                                        }
                                        list($var, $pct) = $varianceDisplay($cur, $prevBal);
                                    @endphp
                                    <tr class="fr-detail-row" :class="isExpanded('a-{{ $subType }}') ? '' : 'fr-hidden'">
                                        <td style="padding-left:2.5rem"><a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a> <span class="code">{{ $item['account']->code }}</span></td>
                                        <td class="r">{{ format_number($cur) }}</td>
                                        <td class="r">{{ $prevBal !== null ? format_number($prevBal) : '—' }}</td>
                                        <td class="r {{ $var !== null && $var < 0 ? 'fr-neg' : '' }}">{{ $var !== null ? format_number($var) : '—' }}</td>
                                        <td class="r">{{ $pct !== null ? number_format($pct, 1).'%' : '—' }}</td>
                                    </tr>
                                @endforeach
                                @php
                                    $subCur = collect($items)->sum('balance');
                                    $subPrev = null;
                                    if ($hasPrev && isset($prevStatement['groups']['asset'][$subType])) {
                                        $subPrev = collect($prevStatement['groups']['asset'][$subType])->sum('balance');
                                    }
                                    list($subVar, $subPct) = $varianceDisplay($subCur, $subPrev);
                                @endphp
                                <tr class="fr-subtotal">
                                    <td style="padding-left:1.5rem" class="fr-ts">Total {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    <td class="r fr-ts">{{ format_number($subCur) }}</td>
                                    <td class="r fr-ts">{{ $subPrev !== null ? format_number($subPrev) : '—' }}</td>
                                    <td class="r fr-ts {{ $subVar !== null && $subVar < 0 ? 'fr-neg' : '' }}">{{ $subVar !== null ? format_number($subVar) : '—' }}</td>
                                    <td class="r fr-ts">{{ $subPct !== null ? number_format($subPct, 1).'%' : '—' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        @php
                            list($atVar, $atPct) = $varianceDisplay($total_assets, $prevAssets);
                        @endphp
                        <tr class="fr-grand-total">
                            <td class="fr-tn">Total Assets</td>
                            <td class="r fr-tn">{{ format_number($total_assets) }}</td>
                            <td class="r fr-tn">{{ $prevAssets !== null ? format_number($prevAssets) : '—' }}</td>
                            <td class="r fr-tn {{ $atVar !== null && $atVar < 0 ? 'fr-neg' : '' }}">{{ $atVar !== null ? format_number($atVar) : '—' }}</td>
                            <td class="r fr-tn">{{ $atPct !== null ? number_format($atPct, 1).'%' : '—' }}</td>
                        </tr>

                        {{-- LIABILITIES --}}
                        <tr class="fr-section-header"><td colspan="5">Liabilities</td></tr>
                        @foreach($groups['liability'] as $subType => $items)
                            @if(!empty($items))
                                <tr class="fr-subsection-header" @click="toggleSection('l-{{ $subType }}')">
                                    <td colspan="5"><span class="fr-expand-icon" :class="isExpanded('l-{{ $subType }}') ? 'open' : ''">▸</span> {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                </tr>
                                @foreach($items as $item)
                                    @php
                                        $cur = $item['balance'];
                                        $prevBal = null;
                                        if ($hasPrev && isset($prevStatement['groups']['liability'][$subType])) {
                                            foreach ($prevStatement['groups']['liability'][$subType] as $pb) {
                                                if ($pb['account']->id === $item['account']->id) { $prevBal = $pb['balance']; break; }
                                            }
                                        }
                                        list($var, $pct) = $varianceDisplay($cur, $prevBal);
                                    @endphp
                                    <tr class="fr-detail-row" :class="isExpanded('l-{{ $subType }}') ? '' : 'fr-hidden'">
                                        <td style="padding-left:2.5rem"><a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a> <span class="code">{{ $item['account']->code }}</span></td>
                                        <td class="r">{{ format_number($cur) }}</td>
                                        <td class="r">{{ $prevBal !== null ? format_number($prevBal) : '—' }}</td>
                                        <td class="r {{ $var !== null && $var < 0 ? 'fr-neg' : '' }}">{{ $var !== null ? format_number($var) : '—' }}</td>
                                        <td class="r">{{ $pct !== null ? number_format($pct, 1).'%' : '—' }}</td>
                                    </tr>
                                @endforeach
                                @php
                                    $subCur = collect($items)->sum('balance');
                                    $subPrev = null;
                                    if ($hasPrev && isset($prevStatement['groups']['liability'][$subType])) {
                                        $subPrev = collect($prevStatement['groups']['liability'][$subType])->sum('balance');
                                    }
                                    list($subVar, $subPct) = $varianceDisplay($subCur, $subPrev);
                                @endphp
                                <tr class="fr-subtotal">
                                    <td style="padding-left:1.5rem" class="fr-ts">Total {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    <td class="r fr-ts">{{ format_number($subCur) }}</td>
                                    <td class="r fr-ts">{{ $subPrev !== null ? format_number($subPrev) : '—' }}</td>
                                    <td class="r fr-ts {{ $subVar !== null && $subVar < 0 ? 'fr-neg' : '' }}">{{ $subVar !== null ? format_number($subVar) : '—' }}</td>
                                    <td class="r fr-ts">{{ $subPct !== null ? number_format($subPct, 1).'%' : '—' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        @php
                            list($tlVar, $tlPct) = $varianceDisplay($total_liabilities, $prevLiabilities);
                        @endphp
                        <tr class="fr-grand-total">
                            <td class="fr-tn">Total Liabilities</td>
                            <td class="r fr-tn">{{ format_number($total_liabilities) }}</td>
                            <td class="r fr-tn">{{ $prevLiabilities !== null ? format_number($prevLiabilities) : '—' }}</td>
                            <td class="r fr-tn {{ $tlVar !== null && $tlVar < 0 ? 'fr-neg' : '' }}">{{ $tlVar !== null ? format_number($tlVar) : '—' }}</td>
                            <td class="r fr-tn">{{ $tlPct !== null ? number_format($tlPct, 1).'%' : '—' }}</td>
                        </tr>

                        {{-- EQUITY --}}
                        <tr class="fr-section-header"><td colspan="5">Equity</td></tr>
                        @foreach($groups['equity'] as $subType => $items)
                            @foreach($items as $item)
                                @php
                                    $cur = $item['balance'];
                                    $prevBal = null;
                                    if ($hasPrev && isset($prevStatement['groups']['equity'][$subType])) {
                                        foreach ($prevStatement['groups']['equity'][$subType] as $pb) {
                                            if ($pb['account']->id === $item['account']->id) { $prevBal = $pb['balance']; break; }
                                        }
                                    }
                                    list($var, $pct) = $varianceDisplay($cur, $prevBal);
                                @endphp
                                <tr>
                                    <td style="padding-left:2.5rem"><a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a> <span class="code">{{ $item['account']->code }}</span></td>
                                    <td class="r">{{ format_number($cur) }}</td>
                                    <td class="r">{{ $prevBal !== null ? format_number($prevBal) : '—' }}</td>
                                    <td class="r {{ $var !== null && $var < 0 ? 'fr-neg' : '' }}">{{ $var !== null ? format_number($var) : '—' }}</td>
                                    <td class="r">{{ $pct !== null ? number_format($pct, 1).'%' : '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        @php
                            list($cyeVar, $cyePct) = $varianceDisplay($current_year_earnings, $prevCYE ?? null);
                        @endphp
                        <tr>
                            <td style="padding-left:2.5rem" class="fr-tl">Current Year Earnings</td>
                            <td class="r">{{ format_number($current_year_earnings) }}</td>
                            <td class="r">{{ ($prevCYE ?? null) !== null ? format_number($prevCYE) : '—' }}</td>
                            <td class="r {{ $cyeVar !== null && $cyeVar < 0 ? 'fr-neg' : '' }}">{{ $cyeVar !== null ? format_number($cyeVar) : '—' }}</td>
                            <td class="r">{{ $cyePct !== null ? number_format($cyePct, 1).'%' : '—' }}</td>
                        </tr>
                        @php
                            list($teVar, $tePct) = $varianceDisplay($total_equity, $prevEquity);
                        @endphp
                        <tr class="fr-grand-total">
                            <td class="fr-tn">Total Equity</td>
                            <td class="r fr-tn">{{ format_number($total_equity) }}</td>
                            <td class="r fr-tn">{{ $prevEquity !== null ? format_number($prevEquity) : '—' }}</td>
                            <td class="r fr-tn {{ $teVar !== null && $teVar < 0 ? 'fr-neg' : '' }}">{{ $teVar !== null ? format_number($teVar) : '—' }}</td>
                            <td class="r fr-tn">{{ $tePct !== null ? number_format($tePct, 1).'%' : '—' }}</td>
                        </tr>

                        {{-- TOTAL L+E --}}
                        @php
                            $totalLE = $total_liabilities + $total_equity;
                            $prevLE = ($prevLiabilities !== null && $prevEquity !== null) ? $prevLiabilities + $prevEquity : null;
                            list($leVar, $lePct) = $varianceDisplay($totalLE, $prevLE);
                        @endphp
                        <tr class="fr-net-total">
                            <td class="fr-tn" style="font-size:.9375rem">Total Liabilities & Equity</td>
                            <td class="r fr-tn" style="font-size:.9375rem">{{ format_number($totalLE) }}</td>
                            <td class="r fr-tn">{{ $prevLE !== null ? format_number($prevLE) : '—' }}</td>
                            <td class="r fr-tn {{ $leVar !== null && $leVar < 0 ? 'fr-neg' : '' }}">{{ $leVar !== null ? format_number($leVar) : '—' }}</td>
                            <td class="r fr-tn">{{ $lePct !== null ? number_format($lePct, 1).'%' : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fr-actionbar">
            <a href="{{ route('accounting.balance-sheet.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost">Export CSV</a>
            <a href="{{ route('accounting.balance-sheet.export-pdf', request()->query()) }}" class="fr-btn fr-btn-cta">Print / PDF</a>
        </div>
    </div>

    @push('scripts')
    <script>
    function frExpandAll() {
        const sections = @json(collect($groups)->flatMap(fn($subTypes, $type) => collect($subTypes)->filter()->keys()->map(fn($k) => $type[0].'-'.$k))->values()->all());
        return {
            expanded: sections.reduce((a, s) => ({...a, [s]: true}), {}),
            isExpanded(s) { return this.expanded[s] !== false; },
            toggleSection(s) { this.expanded[s] = !this.isExpanded(s); },
            expandAll() { sections.forEach(s => this.expanded[s] = true); },
            collapseAll() { sections.forEach(s => this.expanded[s] = false); },
        };
    }
    </script>
    @endpush
</x-app-layout>
