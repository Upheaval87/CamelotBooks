<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt {{ $salesReceipt->receipt_number }}</title>
    <style>
        :root {
            --deep-1: #17565d;
            --sec: #107C7B;
            --ink: #0B2A2D;
            --muted: #52696B;
            --faint: #5F7A7C;
            --line: #e2ecec;
            --border: #dceaea;
            --acc: #0E7473;
            --acc-2: #0b5c5b;
            --headbg: #F0F5F5;
            --rcol: 300px;
            --shadow-paper: 0 2px 6px rgba(10,42,46,.06), 0 24px 60px -18px rgba(8,40,44,.35);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #374151;
            background: #eef4f4;
            -webkit-font-smoothing: antialiased;
        }
        @page {
            size: A4 portrait;
            margin: 0 0 12mm 0;
            @bottom-right {
                content: "{{ __('Receipt') }} · PAGE " counter(page) " OF " counter(pages);
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 8px; font-weight: 700; letter-spacing: .08em;
                color: #107c7b; font-variant-numeric: tabular-nums; padding-right: 40px;
            }
        }
        .stage-pdf { background: #dfe9e9; border-radius: 24px; padding: 44px 18px; min-height: 100vh; }
        .paper {
            max-width: 52rem; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;
            box-shadow: var(--shadow-paper); -webkit-print-color-adjust: exact; print-color-adjust: exact;
            display: flex; flex-direction: column; min-height: calc(100vh - 88px);
        }

        /* meta strip under chrome band */
        .p-meta { display: flex; gap: 1.75rem; padding: 1.25rem 2rem 0; flex-wrap: wrap; }
        .p-meta .mi { display: flex; flex-direction: column; gap: .1875rem; }
        .p-meta .l { font-size: .5625rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); }
        .p-meta .v { font-size: .71875rem; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }

        /* parties */
        .parties { display: grid; grid-template-columns: 1fr var(--rcol); gap: 1.75rem; padding: 1.75rem 2rem .25rem; }
        .p-l { font-size: .625rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--acc); margin-bottom: .5rem; }
        .party .name { font-size: .84375rem; font-weight: 800; color: var(--ink); }
        .party .rows { margin-top: .3125rem; font-size: .65625rem; line-height: 1.75; color: var(--muted); }

        /* items sheet */
        .p-sheet { padding: 1.25rem 2rem 0; }
        .p-sheet .li-wrap { border: 1px solid var(--border); border-radius: 6px; overflow: hidden; background: #fff; margin-top: 0; }
        .p-sheet table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
        .p-sheet thead th {
            background: var(--acc); color: #fff; text-align: left; font-size: .625rem; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase; padding: .6875rem .75rem;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15);
        }
        .p-sheet thead th:first-child { border-radius: 5px 0 0 0; }
        .p-sheet thead th:last-child { border-radius: 0 5px 0 0; }
        .p-sheet thead th.num, .p-sheet td.numr { text-align: right; }
        .p-sheet tbody td { padding: .75rem; font-size: .75rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
        .p-sheet tbody tr:last-child td { border-bottom: none; }
        .p-sheet td.numr { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; color: var(--ink); }

        /* totals */
        .p-totals { display: grid; grid-template-columns: 1fr var(--rcol); gap: 1.75rem; padding: 1rem 2rem 0; align-items: start; }
        .p-totals .trow { display: flex; justify-content: space-between; padding: 6px 0; font-size: .75rem; color: var(--muted); }
        .p-totals .trow .v { color: var(--ink); font-weight: 600; font-variant-numeric: tabular-nums; }
        .p-gt {
            margin-top: .5rem; display: flex; justify-content: space-between; align-items: center; padding: .75rem 1rem; border-radius: 8px;
            background: linear-gradient(90deg, var(--acc), var(--acc-2));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.2), inset 0 -1px 0 rgba(0,0,0,.25);
        }
        .p-gt .l { font-size: .625rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #dff7f6; }
        .p-gt .v { font-size: .9375rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
        .p-words { margin-top: .5rem; font-size: .625rem; font-style: italic; color: var(--muted); text-align: right; }

        /* lower */
        .p-lower { display: grid; grid-template-columns: 1fr var(--rcol); gap: 1.75rem; padding: 1.5rem 2rem 0; }
        .blk { font-size: .75rem; font-weight: 800; color: var(--ink); margin-bottom: .5rem; }
        .p-terms p, .p-pay .rows { font-size: .65625rem; line-height: 1.8; color: var(--muted); }
        .p-pay { border-left: 3px solid var(--acc); padding-left: .875rem; }

        /* auth */
        .p-auth { padding: 1.5rem 2rem 0; }
        .p-auth p { margin-top: .375rem; font-size: .65625rem; color: var(--muted); line-height: 1.6; }
        .p-sig {
            margin-top: 2.75rem; max-width: 16.25rem; border-top: 1.5px dashed #bfd6d5; padding-top: .375rem;
            font-size: .625rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em;
        }

        @media print {
            body { background: none; }
            .stage-pdf { padding: 0; background: none; border-radius: 0; }
            .paper { box-shadow: none; border-radius: 0; max-width: none; min-height: calc(297mm - 12mm); }
            .cbp-foot .cbp-fr { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $company = $salesReceipt->company;
        $companyName = $company?->name ?? config('app.company_name', 'CamelotBooks');
        $tagline = config('app.company_tagline', 'Enterprise Accounting & Advisory Services');
        $methods = $salesReceipt->payments->map(fn ($p) => $p->paymentMethod?->name)->filter()->unique()->values()->implode(', ');
        $customer = $salesReceipt->customer;
        $discountTotal = $salesReceipt->lines->sum(fn ($l) => ($l->unit_price * $l->quantity) * (($l->discount ?? 0) / 100));
        $words = function (float $number): string {
            $number = round($number, 2);
            $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            $scales = ['', 'Thousand', 'Million', 'Billion'];
            $spellHundreds = function (int $n) use (&$spellHundreds, $units, $tens): string {
                $out = '';
                if ($n >= 100) { $out .= $units[intdiv($n, 100)] . ' Hundred'; $n %= 100; if ($n > 0) { $out .= ' '; } }
                if ($n >= 20) { $out .= $tens[intdiv($n, 10)]; $n %= 10; if ($n > 0) { $out .= ' '; } }
                if ($n > 0) { $out .= $units[$n]; }
                return trim($out);
            };
            $whole = (int) floor($number);
            $cents = (int) round(($number - $whole) * 100);
            $chunks = [];
            if ($whole === 0) { $chunks[] = 'Zero'; }
            $scaleIdx = 0;
            do {
                $part = $whole % 1000;
                if ($part > 0) {
                    $word = $spellHundreds($part);
                    if ($scaleIdx > 0) { $word .= ' ' . $scales[$scaleIdx]; }
                    array_unshift($chunks, $word);
                }
                $whole = intdiv($whole, 1000);
                $scaleIdx++;
            } while ($whole > 0 && $scaleIdx < count($scales));
            $text = implode(', ', $chunks) . ' Kwacha';
            if ($cents > 0) {
                $centsWord = $spellHundreds($cents);
                $text .= ' and ' . $centsWord . ' Tambala';
            }
            return $text . ' only.';
        };
    @endphp

    <div class="stage-pdf">
        <div class="paper">
            @include('components.pdf.chrome', [
                'part' => 'header',
                'title' => __('Receipt'),
                'number' => '№ ' . $salesReceipt->receipt_number,
                'companyName' => $companyName,
                'tagline' => $tagline,
            ])

            <div class="p-meta">
                <div class="mi"><span class="l">{{ __('Date') }}</span><span class="v">{{ $salesReceipt->receipt_date?->format('d F Y') ?? '—' }}</span></div>
                <div class="mi"><span class="l">{{ __('Method') }}</span><span class="v">{{ $methods ?: '—' }}</span></div>
                @if($salesReceipt->reference)
                    <div class="mi"><span class="l">{{ __('Reference') }}</span><span class="v">{{ $salesReceipt->reference }}</span></div>
                @endif
            </div>

            <div class="parties">
                <div class="party">
                    <div class="p-l">{{ __('Received From') }}</div>
                    <div class="name">{{ $customer?->name ?? __('Walk-in Customer') }}</div>
                    <div class="rows">
                        {{ __('Attn') }}: {{ $customer?->name ?? __('Walk-in Customer') }}<br>
                        {{ $customer?->address ?: '—' }}<br>
                        {{ __('Phone') }}: {{ $customer?->phone ?: '—' }}<br>
                        {{ __('Email') }}: {{ $customer?->email ?: '—' }}
                    </div>
                </div>
                <div class="party">
                    <div class="p-l">{{ __('From') }}</div>
                    <div class="name">{{ $companyName }}</div>
                    <div class="rows">
                        {{ $company?->address ?: '—' }}<br>
                        {{ $company?->phone ? 'Phone: ' . $company->phone : '—' }}<br>
                        {{ $company?->email ? 'Email: ' . $company->email : '—' }}
                    </div>
                </div>
            </div>

            <div class="p-sheet">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:22%">{{ __('Item') }}</th>
                                <th style="width:35%">{{ __('Description') }}</th>
                                <th class="num" style="width:8%">{{ __('Qty') }}</th>
                                <th class="num" style="width:14%">{{ __('Unit Price') }}</th>
                                <th class="num" style="width:16%">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesReceipt->lines as $line)
                                <tr>
                                    <td style="color:var(--faint);font-size:.625rem">{{ $loop->iteration }}</td>
                                    <td style="font-weight:600;color:var(--ink)">{{ $line->product?->name ?? '—' }}</td>
                                    <td><span style="font-size:.625rem;color:var(--muted)">{{ $line->description }}</span></td>
                                    <td class="numr">{{ format_number($line->quantity, 0) }}</td>
                                    <td class="numr">{{ format_money($line->unit_price) }}</td>
                                    <td class="numr">{{ format_money($line->line_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="color:var(--faint);text-align:center;padding:1rem">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-totals">
                <div></div>
                <div>
                    <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ format_money($salesReceipt->subtotal) }}</span></div>
                    <div class="trow"><span>{{ __('Discount (0%)') }}</span><span class="v">{{ format_money($discountTotal) }}</span></div>
                    <div class="trow"><span>{{ __('VAT (0%)') }}</span><span class="v">{{ format_money($salesReceipt->tax_total) }}</span></div>
                    <div class="p-gt"><span class="l">{{ __('Total Received') }}</span><span class="v">{{ format_money($salesReceipt->total) }}</span></div>
                    <div class="p-words">{{ __('Amount in words') }}: {{ $words($salesReceipt->total) }}</div>
                </div>
            </div>

            <div class="p-lower">
                <div class="p-terms">
                    <h4 class="blk">{{ __('Terms & Notes') }}</h4>
                    <p>
                        @if($salesReceipt->memo)
                            {{ $salesReceipt->memo }}<br><br>
                        @endif
                        {{ __('This receipt acknowledges payment received as of the date above.') }}<br>
                        {{ __('Please quote the receipt number in all correspondence.') }}<br>
                        {{ __('Thank you for your business.') }}
                    </p>
                </div>
                <div class="p-pay">
                    <div class="p-l">{{ __('Payment Details') }}</div>
                    <div class="rows">
                        @forelse($salesReceipt->payments as $payment)
                            {{ $payment->paymentMethod?->name ?? '—' }}
                            @if($payment->reference_number) &mdash; {{ $payment->reference_number }} @endif
                            &mdash; {{ format_money($payment->amount) }}
                            @if($payment->cash_tendered)
                                <br>{{ __('Cash Tendered') }}: {{ format_money($payment->cash_tendered) }}
                                &middot; {{ __('Change') }}: {{ format_money($payment->change_given) }}
                            @endif
                            <br>
                        @empty
                            —
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="p-auth">
                <h4 class="blk">{{ __('Received By') }}</h4>
                <p>{{ __('Prepared and issued by') }} {{ $companyName }}.</p>
                <div class="p-sig">{{ __('Received By — Signature & Date') }}</div>
            </div>

            @include('components.pdf.chrome', [
                'part' => 'footer',
                'contact' => $companyName . ' · ' . ($company?->email ?: 'info@camelotbooks.com') . ' · ' . ($company?->phone ?: '+265 1 234 567'),
                'pageLabel' => __('Receipt') . ' · ' . __('Page 1 of 1'),
            ])
        </div>
    </div>
</body>
</html>
