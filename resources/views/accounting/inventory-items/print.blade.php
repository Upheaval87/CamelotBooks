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
        @page {
            size: A4 portrait;
            margin: 0 0 12mm 0;
            @bottom-right {
                content: "{{ __('Inventory Item') }} · PAGE " counter(page) " OF " counter(pages);
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 8px; font-weight: 700; letter-spacing: .08em;
                color: #107c7b; font-variant-numeric: tabular-nums; padding-right: 40px;
            }
        }
        :root {
            --acc: #107C7B;
            --acc-2: #0C3539;
            --ink: #0B2A2D;
            --muted: #52696B;
            --faint: #5F7A7C;
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
        @media print { body { background: #fff; padding: 0; } .canvas { max-width: none; box-shadow: none; border-radius: 0; min-height: calc(297mm - 12mm); } .cbp-foot .cbp-fr { display: none; } }

        /* meta strip under chrome band */
        .p-meta { display: flex; gap: 1.75rem; padding: 1.25rem 2rem 0; flex-wrap: wrap; }
        .p-meta .mi { display: flex; flex-direction: column; gap: .1875rem; }
        .p-meta .l { font-size: .5625rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); }
        .p-meta .v { font-size: .71875rem; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }

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
        td.ic { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif; font-size: 0.6875rem; font-weight: 700; color: var(--acc); }
        td.strong { font-weight: 700; color: var(--ink); }
        td.empty { text-align: center; color: var(--faint); padding: 1.5rem; }

        .qpdf-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.75rem; padding: 0 2rem; }
        .qpdf-detail .d { display: flex; justify-content: space-between; gap: 1rem; padding: 0.4375rem 0; border-bottom: 1px solid var(--line); }
        .qpdf-detail .d .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .qpdf-detail .d .v { font-size: 0.71875rem; font-weight: 700; color: var(--ink); text-align: right; }
    </style>
</head>
<body onload="window.print()">
    <div class="canvas">

        @include('components.pdf.chrome', [
            'part' => 'header',
            'title' => __('Inventory Item'),
            'number' => $product->sku ?? $product->name,
            'companyName' => $companyName,
        ])

        <div class="p-meta">
            <div class="mi"><span class="l">{{ __('Item') }}</span><span class="v">{{ $product->name }}</span></div>
            <div class="mi"><span class="l">{{ __('Type') }}</span><span class="v">{{ ucfirst($product->type) }}</span></div>
            <div class="mi"><span class="l">{{ __('Base UOM') }}</span><span class="v">{{ $product->getBaseUomName() }}</span></div>
            <div class="mi"><span class="l">{{ __('Printed') }}</span><span class="v">{{ now()->format('M d, Y h:i A') }}</span></div>
        </div>

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

        @include('components.pdf.chrome', [
            'part' => 'footer',
            'contact' => $companyName . ' · ' . __('Generated') . ' ' . now()->format('M d, Y \a\t h:i A'),
            'pageLabel' => __('Inventory Item') . ' · ' . __('Page 1 of 1'),
        ])

    </div>
</body>
</html>
