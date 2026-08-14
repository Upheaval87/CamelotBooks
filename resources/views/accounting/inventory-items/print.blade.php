<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Item — {{ $product->name }}</title>
    @php
        $companyName = $product->company?->name ?? config('app.company_name', 'CamelotBooks');
        $avgUnitCost = $totalOnHand > 0 ? $totalValue / $totalOnHand : 0;
    @endphp
    <style>
        @page { size: A4 portrait; margin: 0; }
        :root {
            --acc: #128F8E;
            --acc-2: #0C3539;
            --ink: #0B2A2D;
            --muted: #5F7476;
            --faint: #8AA5A7;
            --line: #DCEAEA;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: Inter, 'Segoe UI', Arial, system-ui, sans-serif;
            color: var(--ink);
            background: #eef2f3;
            padding: 24px 12px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .canvas {
            position: relative;
            max-width: 52rem;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(10,42,46,.06), 0 24px 64px -16px rgba(10,42,46,.25);
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 48px);
        }
        @media print { body { background: #fff; padding: 0; } .canvas { max-width: none; box-shadow: none; border-radius: 0; min-height: 297mm; } }

        .qpdf-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            background: #F0F5F5;
            padding: 1.125rem 2rem 1.75rem;
        }
        .qpdf-brand { padding-top: 1.75rem; display: flex; gap: 0.875rem; align-items: center; }
        .qpdf-logo {
            width: 3.5rem; height: 3.5rem; border-radius: 14px;
            display: grid; place-items: center;
            color: #fff; font-weight: 800; font-size: 0.875rem; letter-spacing: .02em;
            background: linear-gradient(180deg, var(--acc), var(--acc-2));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.28);
        }
        .qpdf-name { font-size: 1.25rem; font-weight: 800; color: var(--ink); }
        .qpdf-tag { font-size: 0.75rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); margin-top: 2px; }
        .qpdf-right { text-align: right; }
        .qpdf-title { color: var(--acc); font-size: 1.125rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .qpdf-item { margin-top: 6px; font-size: 1.0625rem; font-weight: 800; color: var(--ink); }
        .qpdf-mgrid { display: grid; grid-template-columns: repeat(2, auto); gap: 0.5rem 1.5rem; margin-top: 0.625rem; justify-content: end; }
        .qpdf-mgrid .m { display: flex; gap: 0.5rem; align-items: baseline; }
        .qpdf-mgrid .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .qpdf-mgrid .v { font-size: 0.71875rem; font-weight: 700; color: var(--ink); white-space: nowrap; }

        .qpdf-accent { height: 4px; background: var(--acc); }

        .qpdf-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            padding: 1.5rem 2rem 0.25rem;
        }
        .sum-card { border: 1px solid var(--line); border-radius: 8px; padding: 0.8125rem 0.9375rem; }
        .sum-card .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); }
        .sum-card .v { margin-top: 4px; font-size: 1.0625rem; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }

        .qpdf-sect { padding: 1.25rem 2rem 0.25rem; }
        .qpdf-h4 { font-size: 0.6875rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--acc); margin-bottom: 0.625rem; }

        .qpdf-li { margin: 0.25rem 2rem 0; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--acc);
            color: #fff;
            text-align: left;
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 0.5625rem 0.75rem;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,.18);
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--ink);
        }
        tbody tr:nth-child(even) td { background: #F5F9F9; }
        tbody tr:last-child td { border-bottom: none; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        td.ic { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.6875rem; font-weight: 700; color: var(--acc); }
        td.strong { font-weight: 700; color: var(--ink); }
        td.empty { text-align: center; color: var(--faint); padding: 1.5rem; }

        .qpdf-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.75rem; padding: 0 2rem; }
        .qpdf-detail .d { display: flex; justify-content: space-between; gap: 1rem; padding: 0.4375rem 0; border-bottom: 1px solid var(--line); }
        .qpdf-detail .d .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .qpdf-detail .d .v { font-size: 0.71875rem; font-weight: 700; color: var(--ink); text-align: right; }

        .qpdf-foot {
            margin-top: auto;
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            border-top: 1px solid var(--line);
            font-size: 0.6875rem;
            color: var(--faint);
        }
        .qpdf-foot .p { font-weight: 700; letter-spacing: .08em; white-space: nowrap; }
    </style>
</head>
<body onload="window.print()">
    <div class="canvas">

        <header class="qpdf-head">
            <div class="qpdf-brand">
                <span class="qpdf-logo">CB</span>
                <div>
                    <div class="qpdf-name">{{ $companyName }}</div>
                    <div class="qpdf-tag">{{ __('Enterprise Accounting') }}</div>
                </div>
            </div>
            <div class="qpdf-right">
                <div class="qpdf-title">{{ __('Inventory Item') }}</div>
                <div class="qpdf-item">{{ $product->name }}</div>
                <div class="qpdf-mgrid">
                    <div class="m"><span class="l">{{ __('SKU') }}</span><span class="v">{{ $product->sku ?? '—' }}</span></div>
                    <div class="m"><span class="l">{{ __('Type') }}</span><span class="v">{{ ucfirst($product->type) }}</span></div>
                    <div class="m"><span class="l">{{ __('Base UOM') }}</span><span class="v">{{ $product->getBaseUomName() }}</span></div>
                    <div class="m"><span class="l">{{ __('Printed') }}</span><span class="v">{{ now()->format('M d, Y h:i A') }}</span></div>
                </div>
            </div>
        </header>

        <div class="qpdf-accent"></div>

        <div class="qpdf-summary">
            <div class="sum-card"><div class="l">{{ __('Total On Hand') }}</div><div class="v">{{ format_money($totalOnHand) }}</div></div>
            <div class="sum-card"><div class="l">{{ __('Total Value (FIFO)') }}</div><div class="v">{{ format_money($totalValue) }}</div></div>
            <div class="sum-card"><div class="l">{{ __('Reorder Point') }}</div><div class="v">{{ $product->reorder_point ? format_money($product->reorder_point) : '—' }}</div></div>
            <div class="sum-card"><div class="l">{{ __('Avg Unit Cost') }}</div><div class="v">{{ format_money($avgUnitCost, null, 4) }}</div></div>
        </div>

        <div class="qpdf-sect">
            <div class="qpdf-h4">{{ __('Product Details') }}</div>
        </div>
        <div class="qpdf-detail">
            <div class="d"><span class="l">{{ __('SKU') }}</span><span class="v">{{ $product->sku ?? '—' }}</span></div>
            <div class="d"><span class="l">{{ __('Type') }}</span><span class="v">{{ ucfirst($product->type) }}</span></div>
            <div class="d"><span class="l">{{ __('Base UOM') }}</span><span class="v">{{ $product->getBaseUomName() }}</span></div>
            <div class="d"><span class="l">{{ __('Sales Price') }}</span><span class="v">{{ format_money($product->sales_price) }}</span></div>
            <div class="d"><span class="l">{{ __('Purchase Price') }}</span><span class="v">{{ $product->purchase_price ? format_money($product->purchase_price) : '—' }}</span></div>
            <div class="d"><span class="l">{{ __('Income Account') }}</span><span class="v">{{ $product->incomeAccount->code ?? '' }} {{ $product->incomeAccount->name ?? '—' }}</span></div>
            <div class="d"><span class="l">{{ __('COGS Account') }}</span><span class="v">{{ $product->expenseAccount->code ?? '' }} {{ $product->expenseAccount->name ?? '—' }}</span></div>
            <div class="d"><span class="l">{{ __('Inventory Asset Account') }}</span><span class="v">{{ $product->inventoryAssetAccount->code ?? '' }} {{ $product->inventoryAssetAccount->name ?? '—' }}</span></div>
        </div>

        <div class="qpdf-sect">
            <div class="qpdf-h4">{{ __('Stock by Location') }}</div>
        </div>
        <div class="qpdf-li">
            <table>
                <thead><tr>
                    <th style="width:50%">{{ __('Branch') }}</th>
                    <th style="width:50%" class="num">{{ __('Quantity') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($product->stock as $stock)
                        <tr>
                            <td>{{ $stock->branch?->name ?? 'Main' }}</td>
                            <td class="num strong">{{ format_money($stock->quantity_on_hand) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty">{{ __('No stock records found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="qpdf-sect">
            <div class="qpdf-h4">{{ __('FIFO Cost Layers') }}</div>
        </div>
        <div class="qpdf-li">
            <table>
                <thead><tr>
                    <th style="width:20%">{{ __('Date') }}</th>
                    <th style="width:30%">{{ __('Source') }}</th>
                    <th style="width:15%" class="num">{{ __('Qty Remaining') }}</th>
                    <th style="width:15%" class="num">{{ __('Unit Cost') }}</th>
                    <th style="width:20%" class="num">{{ __('Total Value') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($product->costLayers as $layer)
                        <tr>
                            <td>{{ $layer->date->format('M d, Y') }}</td>
                            <td class="ic">{{ $layer->source_type ?? '—' }}</td>
                            <td class="num">{{ format_money($layer->quantity_remaining) }}</td>
                            <td class="num">{{ format_money($layer->unit_cost, null, 4) }}</td>
                            <td class="num strong">{{ format_money($layer->quantity_remaining * $layer->unit_cost) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">{{ __('No cost layers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($product->uomConversions->isNotEmpty())
            <div class="qpdf-sect">
                <div class="qpdf-h4">{{ __('UOM Conversions') }}</div>
            </div>
            <div class="qpdf-li">
                <table>
                    <thead><tr>
                        <th style="width:25%">{{ __('UOM') }}</th>
                        <th style="width:25%" class="num">{{ __('Conversion Factor') }}</th>
                        <th style="width:25%" class="num">{{ __('Purchase Price') }}</th>
                        <th style="width:25%" class="num">{{ __('Sales Price') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($product->uomConversions as $uom)
                            <tr>
                                <td class="strong">{{ $uom->uom_name }}@if($uom->is_base) <span style="color:var(--faint);font-size:.65rem;text-transform:uppercase;letter-spacing:.06em">({{ __('Base') }})</span>@endif</td>
                                <td class="num">{{ number_format($uom->conversion_factor, 4) }}</td>
                                <td class="num">{{ $uom->purchase_price > 0 ? format_money($uom->purchase_price) : '—' }}</td>
                                <td class="num">{{ $uom->sales_price > 0 ? format_money($uom->sales_price) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <footer class="qpdf-foot">
            <span>{{ $companyName }} · {{ __('Generated') }} {{ now()->format('M d, Y \a\t h:i A') }}</span>
            <span class="p">{{ __('Page 1 of 1') }}</span>
        </footer>

    </div>
</body>
</html>
