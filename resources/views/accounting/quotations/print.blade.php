<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    @php
        $cur = $quotation->currency ?: 'MWK';
        $companyName = $quotation->company?->name ?? config('app.company_name', 'CamelotBooks');
        $customer = $quotation->customer;
        $prepared = $quotation->createdByUser?->name ?? '';
    @endphp
    <style>
        @page { size: A4 portrait; margin: 0; }
        :root {
            --acc: #0E7473;
            --acc-2: #0b5c5b;
            --ink: #0B2A2D;
            --muted: #5F7476;
            --faint: #8AA5A7;
            --line: #DCEAEA;
            --rcol: 300px;
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
        /* §10 canvas */
        .canvas {
            position: relative;
            max-width: 52rem;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(10,42,46,.06), 0 24px 64px -16px rgba(10,42,46,.25);
        }
        @media print { body { background: #fff; padding: 0; } .canvas { max-width: none; box-shadow: none; border-radius: 0; } }

        /* §10 header — non-green band, tight top, no blank row above accent */
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
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
            font-size: 0.875rem;
            letter-spacing: .02em;
            background: linear-gradient(180deg, var(--acc), var(--acc-2));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.28);
        }
        .qpdf-name { font-size: 1.25rem; font-weight: 800; color: var(--ink); }
        .qpdf-tag { font-size: 0.75rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); margin-top: 2px; }
        .qpdf-right { text-align: right; }
        .qpdf-title {
            color: var(--acc);
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .qpdf-num { margin-top: 4px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.84375rem; font-weight: 700; color: var(--ink); }
        .qpdf-mgrid { display: grid; grid-template-columns: repeat(2, auto); gap: 0.5rem 1.5rem; margin-top: 0.625rem; justify-content: end; }
        .qpdf-mgrid .m { display: flex; gap: 0.5rem; align-items: baseline; }
        .qpdf-mgrid .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .qpdf-mgrid .v { font-size: 0.71875rem; font-weight: 700; color: var(--ink); white-space: nowrap; }

        /* accent line */
        .qpdf-accent { height: 4px; background: var(--acc); }

        /* §10 parties grid */
        .qpdf-parties {
            display: grid;
            grid-template-columns: 1fr var(--rcol);
            gap: 1.75rem;
            padding: 1.75rem 2rem 0.25rem;
        }
        .qpdf-lbl { font-size: 0.625rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--acc); margin-bottom: 0.5rem; }
        .qpdf-cname { font-size: 0.84375rem; font-weight: 800; color: var(--ink); }
        .qpdf-rows { margin-top: 0.5rem; font-size: 0.65625rem; line-height: 1.75; color: var(--muted); }
        .qpdf-detail { display: flex; flex-direction: column; gap: 0.375rem; }
        .qpdf-detail .d { display: flex; justify-content: space-between; gap: 1rem; }
        .qpdf-detail .d .l { font-size: 0.625rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); }
        .qpdf-detail .d .v { font-size: 0.71875rem; font-weight: 700; color: var(--ink); }

        /* §10 datasheet */
        .qpdf-li { margin: 1.125rem 2rem 0; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; }
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

        /* §10 totals grid */
        .qpdf-totrow { display: grid; grid-template-columns: 1fr var(--rcol); gap: 1.75rem; padding: 1.25rem 2rem 0; }
        .totals { display: flex; flex-direction: column; }
        .trow { display: flex; justify-content: space-between; gap: 1rem; padding: 0.4375rem 0; border-bottom: 1px solid var(--line); font-size: 0.75rem; color: var(--muted); }
        .trow .v { font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
        .gt { margin-top: 0.625rem; display: flex; justify-content: space-between; align-items: center; padding: 0.625rem 0.875rem; border-radius: 8px; background: linear-gradient(90deg, #128F8E, var(--acc) 60%, var(--acc-2)); box-shadow: inset 0 1px 0 rgba(255,255,255,.2); }
        .gt .gl { font-size: 0.625rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #DFF7F6; }
        .gt .gv { font-size: 0.9375rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }

        /* §10 lower grid — terms/notes + payment col */
        .qpdf-lower {
            display: grid;
            grid-template-columns: 1fr var(--rcol);
            gap: 1.75rem;
            padding: 1.75rem 2rem 0;
        }
        .qpdf-h4 { font-size: 0.75rem; font-weight: 800; color: var(--ink); margin-bottom: 0.5rem; }
        .qpdf-blk { font-size: 0.6875rem; line-height: 1.7; color: var(--muted); }
        .qpdf-blk li { margin-left: 1rem; }
        .qpdf-blk + .qpdf-h4 { margin-top: 1rem; }
        .qpdf-paycol { border-left: 3px solid var(--acc); padding-left: 0.875rem; }
        .qpdf-paycol .p { display: flex; justify-content: space-between; gap: 1rem; padding: 0.3125rem 0; font-size: 0.6875rem; color: var(--muted); }
        .qpdf-paycol .p .l { font-weight: 700; color: var(--ink); }
        .qpdf-paycol .p .v { font-variant-numeric: tabular-nums; }

        /* §10 authorised signature */
        .qpdf-sig { padding: 1.75rem 2rem 0; }
        .qpdf-sig .qpdf-h4 { color: #000; }
        .qpdf-sigline { margin-top: 2.75rem; max-width: 16.25rem; border-top: 1.5px dashed #BFD6D5; }
        .qpdf-signame { margin-top: 0.625rem; font-size: 0.75rem; font-weight: 800; color: var(--ink); }
        .qpdf-sigrole { font-size: 0.6875rem; color: var(--muted); }

        /* §10 footer */
        .qpdf-foot {
            margin-top: 2rem;
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
                <div class="qpdf-title">{{ __('Quotation') }}</div>
                <div class="qpdf-num">{{ $quotation->quotation_number }}</div>
                <div class="qpdf-mgrid">
                    <div class="m"><span class="l">{{ __('Date') }}</span><span class="v">{{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="m"><span class="l">{{ __('Valid Until') }}</span><span class="v">{{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</span></div>
                    <div class="m"><span class="l">{{ __('Reference') }}</span><span class="v">{{ $quotation->reference ?? '—' }}</span></div>
                    <div class="m"><span class="l">{{ __('Currency') }}</span><span class="v">{{ $cur }}</span></div>
                </div>
            </div>
        </header>

        <div class="qpdf-accent"></div>

        <div class="qpdf-parties">
            <div>
                <div class="qpdf-lbl">{{ __('Prepared For') }}</div>
                <div class="qpdf-cname">{{ $customer->name ?? '—' }}</div>
                @if($customer && ($customer->email || $customer->phone))
                    <div class="qpdf-rows">
                        {{ $customer->email ?? '' }}@if($customer->email && $customer->phone)<br>@endif{{ $customer->phone ?? '' }}
                    </div>
                @endif
            </div>
            <div class="qpdf-detail">
                <div class="d"><span class="l">{{ __('Branch') }}</span><span class="v">{{ $quotation->branch?->name ?? '—' }}</span></div>
                <div class="d"><span class="l">{{ __('Prepared By') }}</span><span class="v">{{ $prepared }}</span></div>
                <div class="d"><span class="l">{{ __('Cost Centre') }}</span><span class="v">{{ $quotation->costCenter?->name ?? '—' }}</span></div>
            </div>
        </div>

        <div class="qpdf-li">
            <table>
                <thead><tr>
                    <th style="width:5%">#</th>
                    <th style="width:24%">{{ __('Item') }}</th>
                    <th style="width:33%">{{ __('Description') }}</th>
                    <th style="width:8%" class="num">{{ __('Qty') }}</th>
                    <th style="width:14%" class="num">{{ __('Unit Price') }}</th>
                    <th style="width:16%" class="num">{{ __('Amount') }}</th>
                </tr></thead>
                <tbody>
                    @forelse($quotation->lines as $idx => $line)
                        <tr>
                            <td class="ic">{{ $idx + 1 }}</td>
                            <td class="ic">{{ $line->product?->sku ?? '—' }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="num">{{ number_format($line->quantity, 2) }}</td>
                            <td class="num">{{ format_number($line->unit_price) }}</td>
                            <td class="num strong">{{ format_number($line->line_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">{{ __('No line items.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="qpdf-totrow">
            <div></div>
            <div class="totals">
                <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ $cur }} {{ format_number($quotation->amount) }}</span></div>
                <div class="trow"><span>{{ __('Tax') }}</span><span class="v">{{ $cur }} {{ format_number($quotation->tax_total) }}</span></div>
                <div class="gt"><span class="gl">{{ __('Grand Total') }}</span><span class="gv">{{ $cur }} {{ format_number($quotation->total) }}</span></div>
            </div>
        </div>

        <div class="qpdf-lower">
            <div class="qpdf-terms">
                @if($quotation->memo)
                    <div class="qpdf-h4">{{ __('Notes') }}</div>
                    <div class="qpdf-blk">{{ $quotation->memo }}</div>
                @endif
                <div class="qpdf-h4">{{ __('Terms') }}</div>
                <div class="qpdf-blk"><ul>
                    <li>{{ __('Payment due per stated terms.') }}</li>
                    <li>{{ __('Quotation № must be referenced on acceptance.') }}</li>
                </ul></div>
            </div>
            <div class="qpdf-paycol">
                <div class="qpdf-h4">{{ __('Payment') }}</div>
                <div class="p"><span class="l">{{ __('Bank') }}</span><span class="v">—</span></div>
                <div class="p"><span class="l">{{ __('Branch') }}</span><span class="v">—</span></div>
                <div class="p"><span class="l">{{ __('Account Name') }}</span><span class="v">—</span></div>
                <div class="p"><span class="l">{{ __('Account No') }}</span><span class="v">—</span></div>
                <div class="p"><span class="l">{{ __('Swift') }}</span><span class="v">—</span></div>
            </div>
        </div>

        <div class="qpdf-sig">
            <div class="qpdf-h4">{{ __('Authorised') }}</div>
            <div class="qpdf-sigline"></div>
            <div class="qpdf-signame">{{ $prepared }}</div>
            <div class="qpdf-sigrole">{{ __('Authorised signature') }}</div>
        </div>

        <footer class="qpdf-foot">
            <span>{{ $companyName }} · {{ __('Generated') }} {{ now()->format('M d, Y \a\t h:i A') }}</span>
            <span class="p">{{ __('Page 1 of 1') }}</span>
        </footer>

    </div>
</body>
</html>
