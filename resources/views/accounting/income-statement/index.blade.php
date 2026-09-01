<x-app-layout>
    @php
        $hasComparison = !empty($compareMode) && !empty($comparisonPeriodLabel);
        $showComparison = $hasComparison;
        $cs = $currencySymbol ?? '$';
        $dp = $dp ?? 2;
        $periodLabel = __('For the period') . ' ' . \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' — ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
        $currentPeriodLabel = \Carbon\Carbon::parse($dateFrom)->format('M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M Y');
        $drillParams = http_build_query(array_filter([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'branch_id' => $branchId,
            'cost_center_id' => $costCenterId,
        ]));
    @endphp

    <div class="fr-wrap" x-data="frExpandAll()">
        {{-- branded header — hidden on screen, shown in @media print --}}
        @include('accounting.statement-branded-header', [
            'company' => $company,
            'title' => 'Income Statement',
            'periodLabel' => $periodLabel,
            'currency' => $currency,
            'cs' => $cs,
            'basis' => 'Accrual',
            'preparedBy' => $preparedBy ?? '—',
        ])
        <div class="fr-head">
            <div>
                <h1>{{ __('Income Statement') }}</h1>
                <div class="fr-sub">{{ $currentPeriodLabel }} · {{ $cs }}</div>
            </div>
            <div class="fr-actions">
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" @click="expandAll()">Expand All</button>
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" @click="collapseAll()">Collapse All</button>
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">Print</button>
                <a href="{{ route('accounting.income-statement.export-pdf', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">PDF</a>
                <a href="{{ route('accounting.income-statement.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">Excel</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.income-statement.index') }}" class="fr-filters">
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
            @if(!empty($costCenters) && $costCenters->count())
            <div class="fr-f">
                <label for="cost_center_id">{{ __('Cost Centre') }}</label>
                <select id="cost_center_id" name="cost_center_id">
                    <option value="">{{ __('All') }}</option>
                    @foreach($costCenters as $cc)
                        <option value="{{ $cc->id }}" {{ (int)($costCenterId ?? 0) === $cc->id ? 'selected' : '' }}>{{ $cc->code }} – {{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="fr-f">
                <label for="compare_mode">{{ __('Comparison') }}</label>
                <select id="compare_mode" name="compare_mode">
                    <option value="">No Comparison</option>
                    <option value="prior_period" {{ ($compareMode ?? '') === 'prior_period' ? 'selected' : '' }}>Prior Period</option>
                    <option value="year_ago" {{ ($compareMode ?? '') === 'year_ago' ? 'selected' : '' }}>Year Ago</option>
                </select>
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="fr-btn fr-btn-cta fr-btn-sm">Generate</button>
                <a href="{{ route('accounting.income-statement.index') }}" class="fr-btn fr-btn-ghost fr-btn-sm">Clear</a>
            </div>
        </form>

        <div class="fr-kpis">
            <div class="fr-kpi hero">
                <div class="fr-kpi-l">Total Income</div>
                <div class="fr-kpi-v">{{ format_number($total_income) }}</div>
            </div>
            <div class="fr-kpi">
                <div class="fr-kpi-l">Total Expenses</div>
                <div class="fr-kpi-v">{{ format_number($total_expenses) }}</div>
            </div>
            <div class="fr-kpi">
                <div class="fr-kpi-l">Net Income</div>
                <div class="fr-kpi-v {{ $net_income < 0 ? 'red' : 'green' }}">{{ format_number($net_income) }}</div>
            </div>
            <div class="fr-kpi">
                <div class="fr-kpi-l">Net Margin</div>
                <div class="fr-kpi-v">{{ $total_income != 0 ? number_format(($net_income / abs($total_income)) * 100, 1) : '–' }}%</div>
            </div>
        </div>

        <div class="fr-card">
            <div class="fr-card-head">
                <h2>Income Statement</h2>
                @if($showComparison)
                    <div style="margin-left:auto;font-size:.75rem;color:var(--muted,#5f7476)">
                        {{ $cs }} · {{ $comparisonPeriodLabel }}
                    </div>
                @endif
            </div>
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th style="width:40%">Description</th>
                            <th class="r" style="width:{{ $showComparison ? '15%' : '30%' }}">{{ $cs }}</th>
                            @if($showComparison)
                                <th class="r" style="width:15%">Previous</th>
                                <th class="r" style="width:15%">Variance</th>
                                <th class="r" style="width:15%">%</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        {{-- REVENUE --}}
                        <tr class="fr-section-header">
                            <td colspan="{{ $showComparison ? 5 : 2 }}">Revenue</td>
                        </tr>
                        @foreach($groups['income'] as $subType => $items)
                            @if(!empty($items))
                                <tr class="fr-subsection-header" @click="toggleSection('inc-{{ $subType }}')">
                                    <td colspan="{{ $showComparison ? 5 : 2 }}">
                                        <span class="fr-expand-icon" :class="isExpanded('inc-{{ $subType }}') ? 'open' : ''">▸</span>
                                        {{ ucwords(str_replace('_', ' ', $subType)) }}
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    @php
                                        $current = $item['net'];
                                        $prev = $item['comparison_net'] ?? null;
                                        $variance = ($prev !== null) ? $current - $prev : null;
                                        $variancePct = ($prev !== null && $prev != 0) ? ($variance / abs($prev)) * 100 : null;
                                    @endphp
                                    <tr class="fr-detail-row" :class="isExpanded('inc-{{ $subType }}') ? '' : 'fr-hidden'">
                                        <td style="padding-left:2.5rem">
                                            <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a>
                                            <span class="code">{{ $item['account']->code }}</span>
                                        </td>
                                        <td class="r">{{ format_number($current) }}</td>
                                        @if($showComparison)
                                            <td class="r">{{ $prev !== null ? format_number($prev) : '—' }}</td>
                                            <td class="r {{ $variance !== null && $variance < 0 ? 'fr-neg' : '' }}">{{ $variance !== null ? format_number($variance) : '—' }}</td>
                                            <td class="r">{{ $variancePct !== null ? number_format($variancePct, 1).'%' : '—' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                                @php
                                    $subTotal = collect($items)->sum('net');
                                    $subComp = $showComparison ? collect($items)->sum('comparison_net') : null;
                                    $subVar = ($subComp !== null) ? $subTotal - $subComp : null;
                                    $subPct = ($subComp !== null && $subComp != 0) ? ($subVar / abs($subComp)) * 100 : null;
                                @endphp
                                <tr class="fr-subtotal">
                                    <td style="padding-left:1.5rem" class="fr-ts">Total {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    <td class="r fr-ts">{{ format_number($subTotal) }}</td>
                                    @if($showComparison)
                                        <td class="r fr-ts">{{ $subComp !== null ? format_number($subComp) : '—' }}</td>
                                        <td class="r fr-ts {{ $subVar !== null && $subVar < 0 ? 'fr-neg' : '' }}">{{ $subVar !== null ? format_number($subVar) : '—' }}</td>
                                        <td class="r fr-ts">{{ $subPct !== null ? number_format($subPct, 1).'%' : '—' }}</td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                        @php
                            $compIncome = $showComparison ? ($comparison['total_income'] ?? null) : null;
                            $incVar = ($compIncome !== null) ? $total_income - $compIncome : null;
                            $incPct = ($compIncome !== null && $compIncome != 0) ? ($incVar / abs($compIncome)) * 100 : null;
                        @endphp
                        <tr class="fr-grand-total">
                            <td class="fr-tn">Total Income</td>
                            <td class="r fr-tn">{{ format_number($total_income) }}</td>
                            @if($showComparison)
                                <td class="r fr-tn">{{ $compIncome !== null ? format_number($compIncome) : '—' }}</td>
                                <td class="r fr-tn {{ $incVar !== null && $incVar < 0 ? 'fr-neg' : '' }}">{{ $incVar !== null ? format_number($incVar) : '—' }}</td>
                                <td class="r fr-tn">{{ $incPct !== null ? number_format($incPct, 1).'%' : '—' }}</td>
                            @endif
                        </tr>

                        {{-- EXPENSES --}}
                        <tr class="fr-section-header">
                            <td colspan="{{ $showComparison ? 5 : 2 }}">Expenses</td>
                        </tr>
                        @foreach($groups['expense'] as $subType => $items)
                            @if(!empty($items))
                                <tr class="fr-subsection-header" @click="toggleSection('exp-{{ $subType }}')">
                                    <td colspan="{{ $showComparison ? 5 : 2 }}">
                                        <span class="fr-expand-icon" :class="isExpanded('exp-{{ $subType }}') ? 'open' : ''">▸</span>
                                        {{ ucwords(str_replace('_', ' ', $subType)) }}
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    @php
                                        $current = $item['net'];
                                        $prev = $item['comparison_net'] ?? null;
                                        $variance = ($prev !== null) ? $current - $prev : null;
                                        $variancePct = ($prev !== null && $prev != 0) ? ($variance / abs($prev)) * 100 : null;
                                    @endphp
                                    <tr class="fr-detail-row" :class="isExpanded('exp-{{ $subType }}') ? '' : 'fr-hidden'">
                                        <td style="padding-left:2.5rem">
                                            <a href="{{ route('accounting.general-ledger.account', $item['account']->id).'?'.$drillParams }}" class="fr-tl">{{ $item['account']->name }}</a>
                                            <span class="code">{{ $item['account']->code }}</span>
                                        </td>
                                        <td class="r">{{ format_number($current) }}</td>
                                        @if($showComparison)
                                            <td class="r">{{ $prev !== null ? format_number($prev) : '—' }}</td>
                                            <td class="r {{ $variance !== null && $variance < 0 ? 'fr-neg' : '' }}">{{ $variance !== null ? format_number($variance) : '—' }}</td>
                                            <td class="r">{{ $variancePct !== null ? number_format($variancePct, 1).'%' : '—' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                                @php
                                    $subTotal = collect($items)->sum('net');
                                    $subComp = $showComparison ? collect($items)->sum('comparison_net') : null;
                                    $subVar = ($subComp !== null) ? $subTotal - $subComp : null;
                                    $subPct = ($subComp !== null && $subComp != 0) ? ($subVar / abs($subComp)) * 100 : null;
                                @endphp
                                <tr class="fr-subtotal">
                                    <td style="padding-left:1.5rem" class="fr-ts">Total {{ ucwords(str_replace('_', ' ', $subType)) }}</td>
                                    <td class="r fr-ts">{{ format_number($subTotal) }}</td>
                                    @if($showComparison)
                                        <td class="r fr-ts">{{ $subComp !== null ? format_number($subComp) : '—' }}</td>
                                        <td class="r fr-ts {{ $subVar !== null && $subVar < 0 ? 'fr-neg' : '' }}">{{ $subVar !== null ? format_number($subVar) : '—' }}</td>
                                        <td class="r fr-ts">{{ $subPct !== null ? number_format($subPct, 1).'%' : '—' }}</td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                        @php
                            $compExpenses = $showComparison ? ($comparison['total_expenses'] ?? null) : null;
                            $expVar = ($compExpenses !== null) ? $total_expenses - $compExpenses : null;
                            $expPct = ($compExpenses !== null && $compExpenses != 0) ? ($expVar / abs($compExpenses)) * 100 : null;
                        @endphp
                        <tr class="fr-grand-total">
                            <td class="fr-tn">Total Expenses</td>
                            <td class="r fr-tn">{{ format_number($total_expenses) }}</td>
                            @if($showComparison)
                                <td class="r fr-tn">{{ $compExpenses !== null ? format_number($compExpenses) : '—' }}</td>
                                <td class="r fr-tn {{ $expVar !== null && $expVar < 0 ? 'fr-neg' : '' }}">{{ $expVar !== null ? format_number($expVar) : '—' }}</td>
                                <td class="r fr-tn">{{ $expPct !== null ? number_format($expPct, 1).'%' : '—' }}</td>
                            @endif
                        </tr>

                        {{-- NET INCOME --}}
                        @php
                            $compNetIncome = $showComparison ? ($comparison['net_income'] ?? null) : null;
                            $netVar = ($compNetIncome !== null) ? $net_income - $compNetIncome : null;
                            $netPct = ($compNetIncome !== null && $compNetIncome != 0) ? ($netVar / abs($compNetIncome)) * 100 : null;
                            $netLabel = $net_income >= 0 ? 'Net Income' : 'Net Loss';
                        @endphp
                        <tr class="fr-net-total">
                            <td class="fr-tn" style="font-size:.9375rem">{{ $netLabel }}</td>
                            <td class="r fr-tn" style="font-size:.9375rem">{{ format_number(abs($net_income)) }}</td>
                            @if($showComparison)
                                <td class="r fr-tn">{{ $compNetIncome !== null ? format_number(abs($compNetIncome)) : '—' }}</td>
                                <td class="r fr-tn {{ $netVar !== null && $netVar < 0 ? 'fr-neg' : '' }}">{{ $netVar !== null ? format_number($netVar) : '—' }}</td>
                                <td class="r fr-tn">{{ $netPct !== null ? number_format($netPct, 1).'%' : '—' }}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fr-actionbar">
            <a href="{{ route('accounting.income-statement.export-csv', request()->query()) }}" class="fr-btn fr-btn-ghost">Export CSV</a>
            <a href="{{ route('accounting.income-statement.export-pdf', request()->query()) }}" class="fr-btn fr-btn-cta">Print / PDF</a>
        </div>

        {{-- branded footer — hidden on screen, shown in @media print --}}
        @php
            $_branchLine = $branchId ? ($branches->firstWhere('id', (int) $branchId)->name ?? null) : null;
            $_orgLine = trim(implode(' · ', array_filter([$_branchLine, $company->tax_id ? 'TPIN '.$company->tax_id : null])));
        @endphp
        <footer class="co-foot">
            <span>{{ $company->name ?? 'Company' }}{{ $_orgLine ? ' · '.$_orgLine : '' }}</span>
            <span class="co-foot-pg">Income Statement · <span class="co-pageno"></span></span>
        </footer>
    </div>

    @push('scripts')
    <script>
    document.querySelectorAll('.co-pageno').forEach(function (el) {
        el.textContent = 'Page 1 of 1';
    });
    </script>
    <script>
    function frExpandAll() {
        const sections = @json(collect($groups['income'])->filter()->keys()->map(fn($k) => 'inc-'.$k)
            ->merge(collect($groups['expense'])->filter()->keys()->map(fn($k) => 'exp-'.$k))->values()->all());
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
