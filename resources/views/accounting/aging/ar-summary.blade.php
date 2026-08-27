<x-app-layout>
    @php
        $cs = $currencySymbol ?? '$';
        $drillBranch = $branchId ? '&branch_id='.$branchId : '';
    @endphp

    <div class="fr-wrap">
        <div class="fr-head">
            <div>
                <h1>{{ __('A/R Aging Summary') }}</h1>
                <div class="fr-sub">As at {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }} · {{ $cs }}</div>
            </div>
            <div class="fr-actions">
                <div x-data="{ open: false }" @click.away="open = false" style="position:relative;display:inline-block">
                    <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" @click="open = !open">Send Statements ▾</button>
                    <div x-show="open" x-transition style="position:absolute;right:0;top:100%;z-index:50;background:#fff;border:1px solid #e5e7eb;border-radius:8px;min-width:200px;box-shadow:0 4px 12px rgba(0,0,0,.1);margin-top:4px">
                        <a href="{{ route('accounting.reports.customer-statement') }}" style="display:block;padding:8px 16px;color:#374151;text-decoration:none;font-size:.8125rem">Customer Statement Report</a>
                    </div>
                </div>
                <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">Print</button>
                <a href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar'])) }}" class="fr-btn fr-btn-ghost fr-btn-sm">Excel</a>
                <a href="{{ route('accounting.aging.ar-detail', request()->query()) }}" class="fr-btn fr-btn-ghost fr-btn-sm">View Detail</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.aging.ar-summary') }}" class="fr-filters">
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
                <a href="{{ route('accounting.aging.ar-summary') }}" class="fr-btn fr-btn-ghost fr-btn-sm">Clear</a>
            </div>
        </form>

        <div class="fr-kpis">
            <div class="fr-kpi hero"><div class="fr-kpi-l">Total Outstanding</div><div class="fr-kpi-v">{{ format_number($totals['total']) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">Current</div><div class="fr-kpi-v">{{ format_number($totals['current']) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">1–30 Days</div><div class="fr-kpi-v">{{ format_number($totals['days_1_30']) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">31–60 Days</div><div class="fr-kpi-v">{{ format_number($totals['days_31_60']) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">61–90 Days</div><div class="fr-kpi-v">{{ format_number($totals['days_61_90']) }}</div></div>
            <div class="fr-kpi"><div class="fr-kpi-l">90+ Days</div><div class="fr-kpi-v {{ ($totals['days_90_plus'] ?? 0) > 0 ? 'red' : '' }}">{{ format_number($totals['days_90_plus']) }}</div></div>
        </div>

        <div class="fr-card">
            <div class="fr-card-head">
                <h2>A/R Aging Summary</h2>
            </div>
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th style="width:22%">Customer</th>
                            <th class="r" style="width:13%">Current</th>
                            <th class="r" style="width:13%">1–30 Days</th>
                            <th class="r" style="width:13%">31–60 Days</th>
                            <th class="r" style="width:13%">61–90 Days</th>
                            <th class="r" style="width:13%">90+ Days</th>
                            <th class="r" style="width:13%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.customers.show', $row['customer_id']).'?'.$drillBranch }}" class="fr-tl">{{ $row['customer_name'] }}</a>
                                </td>
                                <td class="r">{{ format_number($row['current']) }}</td>
                                <td class="r">{{ format_number($row['days_1_30']) }}</td>
                                <td class="r">{{ format_number($row['days_31_60']) }}</td>
                                <td class="r">{{ format_number($row['days_61_90']) }}</td>
                                <td class="r">
                                    @if(($row['days_90_plus'] ?? 0) > 0)
                                        <span class="fr-badge fr-badge-red"><span class="fr-badge-dot"></span>{{ format_number($row['days_90_plus']) }}</span>
                                    @else
                                        {{ format_number($row['days_90_plus']) }}
                                    @endif
                                </td>
                                <td class="r" style="font-weight:700">{{ format_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:2rem 1rem;color:var(--faint,#8aa5a7)">No outstanding invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="font-weight:800">Total</td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['current']) }}</td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['days_1_30']) }}</td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['days_31_60']) }}</td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['days_61_90']) }}</td>
                            <td class="r" style="font-weight:800">
                                @if(($totals['days_90_plus'] ?? 0) > 0)
                                    <span class="fr-badge fr-badge-red"><span class="fr-badge-dot"></span>{{ format_number($totals['days_90_plus']) }}</span>
                                @else
                                    {{ format_number($totals['days_90_plus']) }}
                                @endif
                            </td>
                            <td class="r" style="font-weight:800">{{ format_number($totals['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="fr-actionbar">
            <a href="{{ route('accounting.aging.export-csv', array_merge(request()->query(), ['type' => 'ar'])) }}" class="fr-btn fr-btn-ghost">Export CSV</a>
            <button type="button" class="fr-btn fr-btn-cta" onclick="window.print()">Print / PDF</button>
        </div>
    </div>
</x-app-layout>
