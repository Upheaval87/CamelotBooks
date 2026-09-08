<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'board';
            $title = 'Board Pack';
            $subtitle = 'A condensed executive snapshot - KPIs, income statement, working capital, top products and tracking actions.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $kips = $data['kpis'] ?? [];
            $statement = $data['statement'] ?? [];
            $wc = $data['working_capital'] ?? [];
            $products = $data['products'] ?? [];
            $topProducts = array_slice(($products['rows'] ?? []), 0, 5);
        @endphp

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">KPIs</h2>
                    <p class="an-card-s">{{ ($data['period']['from'] ?? '') . ' → ' . ($data['period']['to'] ?? '') }}</p>
                </div>
            </div>
            @if ($kips)
                <div class="an-kpis an-kpis--4">
                    @foreach ([
                        'revenue' => 'Revenue',
                        'gross_profit' => 'Gross Profit',
                        'net_income' => 'Net Income',
                        'cash_balance' => 'Cash Balance',
                    ] as $key => $label)
                        @php
                            $val = (float) ($kips[$key]['value'] ?? 0);
                            $pval = $kips[$key]['prev'] ?? null;
                        @endphp
                        <div class="an-kpi">
                            <div class="l">{{ $label }}</div>
                            <div class="v {{ $val < 0 ? 'dn' : ($val > 0 ? 'up' : '') }}">@money($val)</div>
                            @if ($pval !== null)
                                <div class="d"><span class="vs">vs {{ format_money($pval) }}</span></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No KPI data.</div>
            @endif
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Income Statement</h2>
                @if ($statement)
                    <div class="an-tbl-wrap">
                        <table class="an-tbl">
                            <tbody>
                                @foreach ([
                                    'Revenue' => 'revenue',
                                    'Cost of Goods Sold' => 'cogs',
                                    'Gross Profit' => 'gross_profit',
                                    'Operating Expenses' => 'opex',
                                    'Total Expenses' => 'total_expenses',
                                    'Net Income' => 'net_income',
                                ] as $label => $key)
                                    <tr><td>{{ $label }}</td><td class="r">@money($statement[$key] ?? 0)</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="an-empty">No statement data.</div>
                @endif
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Working Capital</h2>
                @if ($wc)
                    <div class="an-tbl-wrap">
                        <table class="an-tbl">
                            <tbody>
                                @foreach ([
                                    'Cash' => 'cash',
                                    'Receivables' => 'receivables',
                                    'Inventory' => 'inventory',
                                    'Payables' => 'payables',
                                    'Working Capital' => 'working_capital',
                                    'Current Ratio' => 'current_ratio',
                                ] as $label => $key)
                                    @php
                                        $v = $wc[$key] ?? 0;
                                        $isRatio = $key === 'current_ratio';
                                    @endphp
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="r">{{ $isRatio ? (is_numeric($v) ? format_number($v, 2) : '—') : format_money((float) $v) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="an-empty">No working capital data.</div>
                @endif
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Top Products</h2>
            @if ($topProducts)
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="r">Revenue</th>
                                <th class="r">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $p)
                                <tr>
                                    <td>{{ $p['product_name'] ?? '' }}</td>
                                    <td class="r">@money($p['revenue'] ?? 0)</td>
                                    <td class="r">{{ number_format((float) ($p['quantity'] ?? 0), 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="an-empty">No product data.</div>
            @endif
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Tracking Actions</h2>
                    <p class="an-card-s">{{ (int) ($data['outstanding_actions'] ?? 0) }} outstanding</p>
                </div>
                @can('bi.exec')
                    <button class="an-btn an-btn-cta" type="button" onclick="document.getElementById('board-action-form').classList.toggle('an-hidden');">New action</button>
                @endcan
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Owner</th>
                            <th>Due</th>
                            <th class="c">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($data['actions'] ?? []) as $action)
                            @php
                                $done = in_array(strtolower((string) ($action['status'] ?? '')), ['done', 'cancelled']);
                                $statusLabel = $done ? 'Done' : 'Open';
                            @endphp
                            <tr>
                                <td>{{ $action['description'] ?? '' }}</td>
                                <td>{{ $action['owner'] ?? '—' }}</td>
                                <td>{{ $action['due_date'] ?? '—' }}</td>
                                <td class="c"><span class="an-chip {{ $done ? 'c-ok' : 'c-warn' }}">{{ $statusLabel }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="c an-empty-cell">No actions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('bi.exec')
                <form id="board-action-form" method="POST" action="{{ route('bi.actions.save') }}" class="an-filters an-hidden" style="margin-top:14px;align-items:flex-end;">
                    @csrf
                    <span class="an-flabel">Action description
                        <input type="text" name="description" class="an-input" required maxlength="255" placeholder="e.g. Clear stock variance for SKU-011">
                    </span>
                    <span class="an-flabel">Owner
                        <input type="text" name="owner" class="an-input" maxlength="120" placeholder="e.g. Finance Manager">
                    </span>
                    <span class="an-flabel">Due date
                        <input type="date" name="due_date" class="an-input">
                    </span>
                    <span class="an-flabel">Status
                        <select name="status" class="an-input">
                            <option value="open">Open</option>
                            <option value="done">Done</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </span>
                    <button class="an-btn an-btn-cta" type="submit">Add action</button>
                </form>
            @endcan
        </div>
        <p class="an-note">Actions are saved to your company's BI action register and surface across board pack views.</p>
    </div>
</x-app-layout>