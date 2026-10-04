<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Quotation') }} {{ $quotation->quotation_number }}</title>
    @php
        $cur = $quotation->currency ?: 'MWK';
        $currencyName = \App\Models\Currency::where('code', $cur)->value('name') ?: $cur;
        $company = $quotation->company;
        $customer = $quotation->customer;
        $isDraft = $quotation->status === \App\Models\Quotation::STATUS_DRAFT;
        $termsLabels = [
            'receipt' => __('Due on receipt'),
            'net7' => __('Net 7'),
            'net15' => __('Net 15'),
            'net30' => __('Net 30'),
            'net60' => __('Net 60'),
        ];
        $terms = $termsLabels[$quotation->terms_key] ?? ($quotation->terms_key ?: __('Due on receipt'));
        $prepared = $quotation->createdByUser?->name ?? '';
        $preparedDate = optional($quotation->created_at)->format('d M Y') ?? '';
        $words = amount_to_words((float) $quotation->total, $currencyName);
        $printed = now()->format('d M Y') . ' · ' . now()->format('H:i');
        $discount = (float) $quotation->lines->sum('discount');
        $audit = 'QUOT ' . $quotation->quotation_number . ' · ' . strtoupper($quotation->status ?? '') . ' · ' . $printed . ($quotation->emailed_to ? ' · EMAILED ' . $quotation->emailed_to : '');
    @endphp
    <style>
        :root {
            --p-accent: #0E7473;
            --sec: #128F8E;
            --sec-2: #128F8E;
            --p-band: #F0F5F5;
            --p-line: #DCE7E5;
            --p-hair: #EEF3F1;
            --p-ink: #0B2A2D;
            --p-sub: #41585C;
            --p-muted: #5F7476;
            --p-faint: #8AA5A7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Inter, 'Segoe UI', Arial, system-ui, sans-serif; color: var(--p-ink); background: linear-gradient(180deg, #e6edec 0%, #dde6e5 100%); }

        .ptoolbar { position: sticky; top: 0; z-index: 50; display: flex; align-items: center; gap: 12px; padding: 12px 22px; background: rgba(255,255,255,.96); border-bottom: 1px solid var(--p-line); box-shadow: 0 6px 18px -12px rgba(10,42,46,.25); flex-wrap: wrap; }
        .tb-back { display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--p-line); background: #fff; font-size: 12.5px; font-weight: 700; color: var(--p-sub); cursor: pointer; text-decoration: none; }
        .tb-back:hover { border-color: rgba(14,116,115,.4); color: var(--p-accent); }
        .tb-back svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; fill: none; }
        .tb-title { font-size: 13px; font-weight: 800; color: var(--p-ink); }
        .tb-title span { font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; font-size: 11.5px; font-weight: 800; color: var(--p-accent); background: rgba(14,116,115,.08); border: 1px solid rgba(14,116,115,.22); padding: 3px 8px; border-radius: 7px; margin-left: 8px; }
        .tb-spacer { flex: 1; }
        .tb-note { font-size: 10.5px; font-weight: 700; color: var(--p-faint); letter-spacing: .06em; text-transform: uppercase; }
        .tb-btn { display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 15px; border-radius: 10px; border: 1px solid var(--p-line); background: #fff; font-size: 12.5px; font-weight: 700; color: var(--p-sub); cursor: pointer; text-decoration: none; }
        .tb-btn:hover { border-color: rgba(14,116,115,.4); color: var(--p-accent); transform: translateY(-1px); }
        .tb-btn svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; fill: none; }
        .tb-btn.primary { background: linear-gradient(180deg, var(--sec-2), var(--sec)); border-color: transparent; color: #fff; box-shadow: 0 5px 12px -5px rgba(18,143,142,.6), inset 0 1px 0 rgba(255,255,255,.25); }

        .sheet-wrap { display: flex; justify-content: center; padding: 34px 20px 60px; }
        .sheet { position: relative; width: 794px; max-width: 100%; min-height: 1123px; background: #fff; border: 1px solid var(--p-line); box-shadow: 0 2px 6px rgba(10,42,46,.08), 0 30px 70px -30px rgba(10,42,46,.45); overflow: hidden; display: flex; flex-direction: column; }
        .sheet::before { content: ''; display: block; height: 5px; flex: none; background: linear-gradient(90deg, var(--p-accent) 0%, var(--sec-2) 55%, #7FD1C0 100%); }
        .wm { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; z-index: 0; }
        .wm span { transform: rotate(-24deg); font-size: 108px; font-weight: 900; letter-spacing: .18em; color: rgba(14,116,115,.05); text-transform: uppercase; white-space: nowrap; }
        .sheet-body { flex: 1 0 auto; display: flex; flex-direction: column; position: relative; z-index: 1; }

        .v-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 26px 46px 20px; background: linear-gradient(180deg, var(--p-band) 0%, #f7fbfa 100%); border-bottom: 1px solid var(--p-line); }
        .brand { display: flex; gap: 13px; }
        .brand .mark { width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(150deg, var(--p-accent) 0%, #0a5453 100%); color: #fff; font-size: 17px; font-weight: 900; display: grid; place-items: center; box-shadow: inset 0 1px 0 rgba(255,255,255,.25); }
        .brand .bn { font-size: 15.5px; font-weight: 900; letter-spacing: -0.015em; color: var(--p-ink); }
        .brand .bl { font-size: 10px; font-weight: 600; color: var(--p-muted); line-height: 1.55; margin-top: 3px; }
        .docblock { text-align: right; }
        .docblock .dt { font-size: 9.5px; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; color: var(--p-accent); }
        .docblock .dn { font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; font-size: 19px; font-weight: 800; color: var(--p-ink); letter-spacing: -0.01em; margin-top: 4px; }
        .docblock .dst { display: inline-flex; align-items: center; gap: 6px; margin-top: 7px; padding: 4px 11px; border-radius: 999px; background: rgba(14,116,115,.10); border: 1px solid rgba(14,116,115,.35); color: var(--p-accent); font-size: 9.5px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .docblock .dst i { width: 5px; height: 5px; border-radius: 50%; background: var(--p-accent); }
        .docblock .dst.draftst { background: rgba(138,163,162,.14); border-color: rgba(138,163,162,.4); color: var(--p-muted); }
        .docblock .dst.draftst i { background: var(--p-muted); }
        .docblock .dp { font-size: 9.5px; font-weight: 600; color: var(--p-faint); margin-top: 6px; }

        .meta { display: grid; grid-template-columns: repeat(4, 1fr); border-bottom: 1px solid var(--p-line); }
        .meta .cell { padding: 10px 16px; border-left: 1px solid var(--p-hair); }
        .meta .cell:first-child { border-left: none; }
        .meta .cell:nth-child(n+5) { border-top: 1px solid var(--p-hair); }
        .meta .l { font-size: 8px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--p-faint); }
        .meta .v { font-size: 11px; font-weight: 700; color: var(--p-ink); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .meta .v.mono { font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; font-size: 10.5px; color: var(--p-accent); }

        .vlines { padding: 20px 46px 0; }
        .vlines .lt { font-size: 9.5px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--p-muted); margin-bottom: 9px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: var(--p-accent); color: #fff; font-size: 8.5px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; padding: 8px 12px; text-align: left; }
        thead th.r { text-align: right; }
        thead th:first-child { border-radius: 6px 0 0 6px; }
        thead th:last-child { border-radius: 0 6px 6px 0; }
        tbody td { padding: 9px 12px; border-bottom: 1px solid var(--p-hair); font-size: 11px; color: var(--p-sub); vertical-align: top; }
        tbody td.no { width: 30px; color: var(--p-faint); font-weight: 700; }
        tbody td.num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: var(--p-ink); white-space: nowrap; }
        tr.totals td { border-bottom: none; border-top: 3px double var(--p-ink); padding: 10px 12px; background: var(--p-band); }
        tr.totals td.lbl { text-align: right; text-transform: uppercase; letter-spacing: .14em; font-size: 9.5px; color: var(--p-muted); font-weight: 800; }
        tr.words td { padding: 9px 12px; border-bottom: 1px solid var(--p-line); background: #fbfdfc; }
        .words-l { font-size: 8px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--p-faint); margin-right: 8px; }
        .words-v { font-size: 11px; font-weight: 700; color: var(--p-sub); font-style: italic; }

        .qpanels { display: grid; grid-template-columns: 1.35fr 1fr; gap: 18px; margin: 16px 46px 0; }
        .narr { border: 1px solid var(--p-line); border-radius: 6px; overflow: hidden; }
        .narr .nh { padding: 7px 12px; background: var(--p-band); border-bottom: 1px solid var(--p-hair); font-size: 8px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--p-muted); }
        .narr .nb { padding: 10px 12px; font-size: 10.5px; font-weight: 500; color: var(--p-sub); line-height: 1.6; }
        .tlist { padding: 10px 12px; display: flex; flex-direction: column; gap: 6px; }
        .tlist div { font-size: 9.5px; font-weight: 600; color: var(--p-sub); line-height: 1.5; padding-left: 12px; position: relative; }
        .tlist div::before { content: ''; position: absolute; left: 0; top: 5px; width: 5px; height: 5px; border-radius: 50%; background: var(--p-accent); opacity: .55; }

        .sigs { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin: 26px 46px 34px; }
        .sig { text-align: center; }
        .sig .rule { border-bottom: 1px solid var(--p-ink); height: 34px; }
        .sig .sl { margin-top: 7px; font-size: 8.5px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--p-muted); }
        .sig .sn { margin-top: 3px; font-size: 10.5px; font-weight: 700; color: var(--p-ink); }
        .sig .sd { font-size: 9.5px; font-weight: 600; color: var(--p-faint); margin-top: 1px; }

        .v-foot { margin-top: auto; padding: 12px 46px; border-top: 1px solid var(--p-line); background: #fbfdfc; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex: none; position: relative; z-index: 1; }
        .v-foot .fa { font-family: ui-monospace, 'SFMono-Regular', Menlo, Consolas, monospace; font-size: 8.5px; font-weight: 600; color: var(--p-faint); }
        .v-foot .fb { font-size: 8.5px; font-weight: 600; color: var(--p-faint); text-align: center; flex: 1; }
        .v-foot .fc { font-size: 9px; font-weight: 800; color: var(--p-muted); letter-spacing: .08em; white-space: nowrap; }

        @page { size: A4; margin: 10mm; }
        @media print {
            body { background: #fff; }
            .ptoolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .sheet { width: auto; min-height: 277mm; border: none; box-shadow: none; }
        }
        @media (max-width: 760px) { .qpanels { grid-template-columns: 1fr; } .sigs { grid-template-columns: 1fr; } .sheet-wrap { padding: 16px 8px 40px; } }
    </style>
</head>
<body>
    <div class="ptoolbar">
        <a class="tb-back" href="{{ route('accounting.quotations.index') }}">
            <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            {{ __('Back') }}
        </a>
        <span class="tb-title">{{ __('Print preview') }}<span>{{ $quotation->quotation_number }}</span></span>
        <span class="tb-spacer"></span>
        <span class="tb-note">{{ __('A4 · Portrait · Ctrl+P also works') }}</span>
        <a class="tb-btn" href="{{ route('accounting.quotations.pdf', $quotation) }}">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h2"/></svg>
            {{ __('Save as PDF') }}
        </a>
        <button type="button" class="tb-btn primary" onclick="window.print()">
            <svg viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            {{ __('Print') }}
        </button>
    </div>

    <div class="sheet-wrap">
        <div class="sheet">
            @if($isDraft)<div class="wm"><span>{{ __('Draft') }}</span></div>@endif
            <div class="sheet-body">
                <div class="v-head">
                    <div class="brand">
                        <div class="mark">{{ mb_substr($company?->name ?? config('app.name'), 0, 1) }}</div>
                        <div>
                            <div class="bn">{{ $company?->name ?? config('app.name') }}</div>
                            <div class="bl">
                                {{ $company?->address ?? __('Head Office') }}<br>
                                @if($company?->tin) {{ __('TIN') }} {{ $company->tin }} @endif
                            </div>
                        </div>
                    </div>
                    <div class="docblock">
                        <div class="dt">{{ __('Quotation') }}</div>
                        <div class="dn">{{ $quotation->quotation_number }}</div>
                        <div class="dst {{ $isDraft ? 'draftst' : '' }}"><i></i>{{ $isDraft ? __('Draft') : __('Issued') }}</div>
                        <div class="dp">{{ __('Printed') }} {{ $printed }}</div>
                    </div>
                </div>

                <div class="meta">
                    <div class="cell"><div class="l">{{ __('Customer') }}</div><div class="v">{{ $customer->name ?? $quotation->customer_name ?? '—' }}</div></div>
                    <div class="cell"><div class="l">{{ __('Contact') }}</div><div class="v">{{ $quotation->contact_person ?: ($customer->display_name ?? '—') }}</div></div>
                    <div class="cell"><div class="l">{{ __('Email') }}</div><div class="v">{{ $quotation->email ?: ($customer->email ?? '—') }}</div></div>
                    <div class="cell"><div class="l">{{ __('Phone') }}</div><div class="v">{{ $quotation->phone ?: ($customer->phone ?? '—') }}</div></div>
                    <div class="cell"><div class="l">{{ __('Quote Date') }}</div><div class="v">{{ optional($quotation->quotation_date)->format('d M Y') ?? '—' }}</div></div>
                    <div class="cell"><div class="l">{{ __('Valid Until') }}</div><div class="v">{{ optional($quotation->valid_until)->format('d M Y') ?? '—' }}</div></div>
                    <div class="cell"><div class="l">{{ __('Currency') }}</div><div class="v">{{ $cur }} · {{ $currencyName }}</div></div>
                    <div class="cell"><div class="l">{{ __('Payment Terms') }}</div><div class="v">{{ $terms }}</div></div>
                    <div class="cell"><div class="l">{{ __('Branch') }}</div><div class="v">{{ $quotation->branch?->name ?? __('Head Office') }}</div></div>
                    <div class="cell"><div class="l">{{ __('Cost Centre') }}</div><div class="v">{{ $quotation->costCenter?->name ?? '—' }}</div></div>
                    <div class="cell"><div class="l">{{ __('Prepared By') }}</div><div class="v">{{ $prepared ?: '—' }}</div></div>
                    <div class="cell"><div class="l">{{ __('Reference') }}</div><div class="v mono">{{ $quotation->reference ?? '—' }}</div></div>
                </div>

                <div class="vlines">
                    <div class="lt">{{ __('Line Items') }}</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:34px">#</th>
                                <th>{{ __('Description') }}</th>
                                <th class="r" style="width:70px">{{ __('Qty') }}</th>
                                <th class="r" style="width:110px">{{ __('Unit Price') }}</th>
                                <th class="r" style="width:70px">{{ __('Disc %') }}</th>
                                <th class="r" style="width:90px">{{ __('Tax %') }}</th>
                                <th class="r" style="width:120px">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotation->lines as $idx => $line)
                                <tr>
                                    <td class="no">{{ $idx + 1 }}</td>
                                    <td>{{ $line->description ?: ($line->item_name ?: ($line->item_code ?: '—')) }}</td>
                                    <td class="num">{{ number_format($line->quantity, 0) }}</td>
                                    <td class="num">{{ format_number($line->unit_price) }}</td>
                                    <td class="num">{{ number_format($line->disc_pct, 0) }}</td>
                                    <td class="num">{{ number_format($line->tax_rate, 0) }}</td>
                                    <td class="num">{{ format_number($line->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="num">{{ __('No line items.') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tbody>
                            <tr><td class="num lbl" colspan="6">{{ __('Subtotal') }}</td><td class="num">{{ $cur }} {{ format_number($quotation->amount) }}</td></tr>
                            @if($discount)
                                <tr><td class="num lbl" colspan="6">{{ __('Discount') }}</td><td class="num">−{{ $cur }} {{ format_number($discount) }}</td></tr>
                            @endif
                            <tr><td class="num lbl" colspan="6">{{ __('Tax') }}</td><td class="num">{{ $cur }} {{ format_number($quotation->tax_total) }}</td></tr>
                            <tr class="totals"><td class="lbl" colspan="6">{{ __('Grand Total') }}</td><td class="num">{{ $cur }} {{ format_number($quotation->total) }}</td></tr>
                            <tr class="words"><td colspan="7"><span class="words-l">{{ __('Amount in words') }}</span><span class="words-v">{{ $words }}</span></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="qpanels">
                    <div class="narr">
                        <div class="nh">{{ __('Notes') }}</div>
                        <div class="nb">{{ $quotation->memo ?: '—' }}</div>
                    </div>
                    <div class="narr">
                        <div class="nh">{{ __('Terms & Conditions') }}</div>
                        <div class="tlist">
                            <div>{{ $terms }}</div>
                            <div>{{ __('Quotation number must be referenced on acceptance.') }}</div>
                            <div>{{ __('Prices are valid until the date stated above.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="sigs">
                    <div class="sig">
                        <div class="rule"></div>
                        <div class="sl">{{ __('Prepared By') }}</div>
                        <div class="sn">{{ $prepared ?: '—' }}</div>
                        <div class="sd">{{ $preparedDate }}</div>
                    </div>
                    <div class="sig">
                        <div class="rule"></div>
                        <div class="sl">{{ __('Customer Acceptance') }}</div>
                        <div class="sn">{{ __('Signature & Date') }}</div>
                        <div class="sd">{{ __('I accept this quotation') }}</div>
                    </div>
                </div>

                <div class="v-foot">
                    <div class="fa">{{ $audit }}</div>
                    <div class="fb">{{ __('This quotation is computer generated and is valid for the period stated.') }}</div>
                    <div class="fc">{{ __('PAGE 1 OF 1') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
