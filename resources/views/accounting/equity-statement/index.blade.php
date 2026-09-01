<x-app-layout>
    @push('styles')
    <style>
        /* ==================================================================
           Statement of Changes in Equity — SOCE page (APPENDIX A)
           Screen: clean app view — brand chrome (.doc-h/.meta/.co-foot)
           is present in the DOM but hidden, shown only in @media print.
           Toolbar/filters/presets are screen-only, hidden in print.
           ================================================================== */
        .soc-phead h1{font-size:1.5rem;font-weight:800;letter-spacing:-.02em;color:var(--ink,#0B3437)}
        .soc-sub{font-size:.875rem;color:var(--muted,#5f7476);margin-top:.25rem}

        /* zero-toggle link (screen only) */
        .soc-zerolink{display:inline-flex;align-items:center;gap:.5rem;height:2.25rem;padding:0 .9rem;border-radius:999px;border:1px solid var(--border,#dceaea);background:#fff;color:var(--ink,#0B3437);font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .14s}
        .soc-zerolink:hover{border-color:#128F8E;color:#128F8E}
        .soc-zerolink svg{width:15px;height:15px}

        /* preset chips */
        .soc-chips{display:flex;flex-wrap:wrap;gap:.5rem;flex:1 1 100%;margin-bottom:.125rem}
        .soc-chip{display:inline-flex;align-items:center;height:2rem;padding:0 .9rem;border-radius:999px;border:1px solid var(--border,#dceaea);background:#fff;color:var(--ink,#0B3437);font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .14s}
        .soc-chip:hover{border-color:#128F8E;color:#128F8E}
        .soc-chip.on{background:linear-gradient(135deg,#128F8E,#0f7d7c);color:#fff;border-color:transparent;box-shadow:0 2px 8px rgba(18,143,142,.25)}
        .soc-chip.off{opacity:.65}
        .soc-chip .dot{width:7px;height:7px;border-radius:50%;margin-right:.4rem;background:rgba(11,52,55,.28)}
        .soc-chip.on .dot{background:#fff}

        .fr-filters{align-items:flex-end}

        /* tools row (screen only) */
        .soc-tools{display:flex;align-items:center;justify-content:flex-end;gap:.625rem;flex-wrap:wrap;margin:0 0 1rem}

        /* the sheet card — turns into the branded doc when printed */
        .soc-sheet{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);border:1px solid var(--border,#dceaea);border-radius:16px;box-shadow:0 1px 3px rgba(10,42,46,.05);overflow:hidden;margin-bottom:1.25rem}
        .soc-sheet-inner{padding:1.5rem 1.75rem}

        /* APPENDIX A sheet chrome — hidden on screen */
        .soc-doc-h,.soc-meta,.soc-co-foot{display:none}

        /* table */
        .soc-table{width:100%;border-collapse:collapse;font-size:.875rem;min-width:640px}
        .soc-table thead th{text-align:right;font-size:.75rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted,#5f7476);padding:.75rem .875rem;border-bottom:2px solid var(--ink,#0B3437);font-variant-numeric:tabular-nums;white-space:nowrap}
        .soc-table thead th.lbl{text-align:left}
        .soc-table tbody td{padding:.625rem .875rem;border-bottom:1px solid var(--line,#e2ecec);vertical-align:middle;color:var(--ink,#0B3437)}
        .soc-table td.amt{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
        .soc-table td.ac{width:1%;white-space:nowrap}
        .soc-code{display:inline-block;width:40px;margin-right:.5rem;color:var(--faint,#8aa5a7);font-family:ui-monospace,Consolas,monospace;font-size:.6875rem;font-weight:600}
        .soc-neg{color:#B91C1C}
        .soc-zero .soc-name,.soc-zero .amt,.soc-zero .soc-code{color:#9CA3AF}
        .soc-subtotal td{border-top:1.5px dashed var(--line,#e2ecec);font-weight:700;color:var(--ink-2,#12393c);background:#FBFDFD}
        .soc-total td{border-top:2px solid var(--ink,#0B3437);border-bottom:3px double var(--ink,#0B3437);background:rgba(14,110,103,.06);font-weight:800;color:var(--ink,#0B3437)}
        .soc-empty{padding:20px 14px;text-align:center;color:var(--faint,#8aa5a7)}

        .soc-hide-zero tr.soc-zero{display:none}

        @media (max-width:768px){
            .soc-sheet-inner{padding:1rem}
        }

        /* ============ print: branded sheet + visibility matrix ============ */
        @media print{
            .soc-phead,.soc-filters,.soc-tools,.fr-filters,.fr-actions,.fr-btn{display:none !important}
            .fr-head{border-bottom:none !important;padding:0 !important;margin:0 !important}
            .fr-wrap{padding:0 !important;max-width:none !important;margin:0 !important}
            .soc-sheet{border:1px solid var(--line,#dceaea) !important;border-radius:4px !important;box-shadow:none !important;backdrop-filter:none !important;overflow:hidden !important;page-break-inside:avoid;margin:0 !important}
            .soc-sheet-inner{padding:34px 42px 40px}
            .soc-doc-h,.soc-meta,.soc-co-foot{display:block}

            /* header lockup — logo + company on the SAME row */
            .soc-doc-h{display:flex;align-items:center;gap:14px}
            .soc-c-logo{width:48px;height:48px;border-radius:12px;flex:0 0 auto;overflow:hidden;display:flex;align-items:center;justify-content:center;position:relative;background:linear-gradient(180deg,#0E6E67,#0A5C56);color:#fff}
            .soc-c-logo img{width:100%;height:100%;object-fit:contain;padding:2px;background:#fff}
            .soc-c-logo .mono{font-size:21px;font-weight:800;line-height:1;color:#F4FBFB}
            .soc-c-logo::after{content:'';position:absolute;left:6px;right:6px;bottom:4px;height:3px;border-radius:2px;background:linear-gradient(90deg,#C9A227,#D9B84A)}
            .soc-c-id{min-width:0}
            .soc-c-company{font-size:19px;font-weight:800;letter-spacing:.01em;color:var(--ink,#0B3437);line-height:1.1}
            .soc-c-orgline{margin-top:4px;font-size:9.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted,#5f7476)}
            .soc-c-rule{height:2px;background:var(--ink,#0B3437);border:0;margin:18px 0 20px}
            .soc-c-title{font-size:15px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:var(--ink,#0B3437)}
            .soc-c-period{margin-top:6px;font-size:11px;font-weight:600;color:var(--muted,#5f7476)}

            /* meta strip — 4-cell hairline */
            .soc-meta{display:grid;grid-template-columns:repeat(4,1fr);margin:18px 0 20px;border:1px solid var(--line,#dceaea);border-radius:10px;overflow:hidden}
            .soc-meta-cell{padding:11px 14px;border-right:1px solid var(--line,#dceaea)}
            .soc-meta-cell:last-child{border-right:none}
            .soc-meta-label{font-size:8.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--faint,#8aa5a7)}
            .soc-meta-value{margin-top:4px;font-size:12px;font-weight:700;color:#000;font-variant-numeric:tabular-nums}

            .soc-table{min-width:0;font-size:12px}
            .soc-table thead th{font-size:10px;padding:9px 8px 8px;border-bottom:1.5px solid var(--ink,#0B3437);color:var(--muted,#5f7476)}
            .soc-table tbody td{font-size:12px;padding:8px 8px}
            .soc-table td.amt{white-space:nowrap}
            .soc-code{width:40px;font-size:10px}
            .soc-zero .soc-name,.soc-zero .amt,.soc-zero .soc-code{color:#6b7280}
            .soc-subtotal td{font-size:12.5px;border-top:1.5px dashed var(--line,#dceaea)}
            .soc-total td{font-size:13px}

            /* footer — co-foot pinned to last page */
            .soc-co-foot{display:flex;justify-content:space-between;gap:16px;margin-top:38px;padding-top:13px;border-top:1px solid var(--line,#dceaea);font-size:10px;color:var(--muted,#5f7476)}
            .soc-co-foot .fr{font-variant-numeric:tabular-nums}

            .soc-hide-zero tr.soc-zero{display:table-row}
            .soc-sheet-inner,.soc-doc-h,.soc-meta,.soc-table,.soc-co-foot{break-inside:avoid}
            .soc-table tr{break-inside:avoid}
            .soc-total td{background:rgba(14,110,103,.06) !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        }
    </style>
    @endpush

    @php
        $n = function ($v) use ($dp) {
            $v = (float) $v;
            return $v < 0 ? '('.number_format(abs($v), $dp, '.', ',').')' : number_format($v, $dp, '.', ',');
        };
        $company    = $meta['company'] ?? null;
        $companyName = $company->name ?? 'Company';
        $basis      = $meta['basis']      ?? 'Accrual';
        $preparedAt = $meta['preparedAt']  ?? now();
        $preparedBy = $meta['preparedBy']  ?? null;
        $branchLine = $meta['branchLine'] ?? null;
        $tpin       = $meta['tpin']       ?? ($company->tax_id ?? null);
        $orgLine    = trim(implode(' · ', array_filter([$branchLine, $tpin ? 'TPIN '.$tpin : null])));
        $logoPath   = $company->logo ?? null;
        $hasLogo    = $logoPath && file_exists(public_path('storage/'.$logoPath));
        $initials   = strtoupper(mb_substr(trim($companyName), 0, 2));

        $queryBase = array_filter([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'branch_id' => $branchId,
            'zero'      => $showZero ? '1' : '0',
        ], fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="fr-wrap">
        <!-- ============ phead (screen only) ============ -->
        <div class="fr-head soc-phead">
            <div>
                <h1>{{ __('Statement of Changes in Equity') }}</h1>
                <div class="soc-sub">
                    {{ __('For the period') }} {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} ·
                    {{ $currency }} ({{ $cs }}) · {{ $basis }}
                </div>
            </div>
            @if(! empty($meta['check']))
                <div class="tie fr-banner ok">
                    <div class="fr-banner-ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span>{{ $meta['check'] }}</span>
                </div>
            @endif
        </div>

        <!-- ============ filters (screen only) ============ -->
        <form method="GET" action="{{ route('accounting.equity-statement.index') }}" class="fr-filters soc-filters">
            <div class="soc-chips">
                @foreach($presets as $key => $p)
                    <a href="{{ route('accounting.equity-statement.index', array_merge($queryBase, ['date_from' => $p['from'], 'date_to' => $p['to']])) }}"
                       class="soc-chip {{ $activePreset === $key ? 'on' : '' }}"
                       @if($activePreset === $key) aria-current="true" @endif>
                        {{ $p['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('accounting.equity-statement.index', array_merge($queryBase, ['date_from' => $dateFrom, 'date_to' => $dateTo])) }}"
                   class="soc-chip {{ $activePreset === 'custom' ? 'on' : 'off' }}"
                   @if($activePreset === 'custom') aria-current="true" @endif>
                    {{ __('Custom') }}
                </a>
            </div>

            <div class="fr-f">
                <label for="date_from">{{ __('From Date') }}</label>
                <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="fr-f">
                <label for="date_to">{{ __('To Date') }}</label>
                <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="fr-f">
                <label for="branch_id">{{ __('Branch') }}</label>
                <x-scoped-search-field
                    name="branch_id"
                    mode="client"
                    :items="$branches->map(fn ($b) => ['id' => $b->id, 'label' => $b->name, 'subtitle' => $b->code])->values()"
                    :value="$branchId ?? ''"
                    :label="$branchId ? ($branches->firstWhere('id', (int) $branchId)?->name ?? '') : ''"
                    placeholder="{{ __('All Branches') }}"
                />
            </div>
            <div class="fr-f" style="flex:0 0 auto">
                <div style="display:flex;gap:.5rem">
                    <button type="submit" class="fr-btn fr-btn-cta fr-btn-sm">{{ __('Generate') }}</button>
                    <a href="{{ route('accounting.equity-statement.index', ['zero' => $showZero ? '1' : '0']) }}" class="fr-btn fr-btn-ghost fr-btn-sm">{{ __('Clear') }}</a>
                </div>
            </div>
        </form>

        <!-- ============ sheet (brand chrome hidden on screen) ============ -->
        <div class="soc-sheet">
            <div class="soc-sheet-inner">
                <!-- doc-h — hidden on screen, shown in print -->
                <header class="soc-doc-h">
                    <div class="soc-c-logo">
                        @if($hasLogo)
                            <img src="{{ asset('storage/'.$logoPath) }}" alt="">
                        @else
                            <span class="mono">{{ $initials }}</span>
                        @endif
                    </div>
                    <div class="soc-c-id">
                        <div class="soc-c-company">{{ $companyName }}</div>
                        @if($orgLine)
                            <div class="soc-c-orgline">{{ $orgLine }}</div>
                        @endif
                    </div>
                </header>
                <hr class="soc-c-rule">

                <div class="soc-c-title">{{ __('Statement of Changes in Equity') }}</div>
                <div class="soc-c-period">{{ __($meta['periodLabel'] ?? '') }}</div>

                <!-- meta — hidden on screen, shown in print -->
                <div class="soc-meta">
                    <div class="soc-meta-cell">
                        <div class="soc-meta-label">{{ __('Currency') }}</div>
                        <div class="soc-meta-value">{{ $currency }} ({{ $cs }})</div>
                    </div>
                    <div class="soc-meta-cell">
                        <div class="soc-meta-label">{{ __('Basis') }}</div>
                        <div class="soc-meta-value">{{ $basis }}</div>
                    </div>
                    <div class="soc-meta-cell">
                        <div class="soc-meta-label">{{ __('Prepared') }}</div>
                        <div class="soc-meta-value">{{ \Carbon\Carbon::parse($preparedAt)->format('d M Y') }}</div>
                    </div>
                    <div class="soc-meta-cell">
                        <div class="soc-meta-label">{{ __('Prepared By') }}</div>
                        <div class="soc-meta-value">{{ $preparedBy }}</div>
                    </div>
                </div>

                <!-- ============ tools (screen only) ============ -->
                <div class="soc-tools">
                    <a href="{{ route('accounting.equity-statement.index', array_merge($queryBase, ['zero' => $showZero ? '0' : '1'])) }}"
                       class="soc-zerolink">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        {{ $showZero ? __('Hide zero-balance accounts') : __('Show zero-balance accounts') }}
                    </a>
                    <button type="button" class="fr-btn fr-btn-ghost fr-btn-sm" onclick="window.print()">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.equity-statement.export-csv', $queryBase) }}" class="fr-btn fr-btn-ghost fr-btn-sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                        {{ __('Excel') }}
                    </a>
                    <a href="{{ route('accounting.equity-statement.export-pdf', $queryBase) }}" class="fr-btn fr-btn-cta fr-btn-sm">{{ __('PDF') }}</a>
                </div>

                <!-- the table -->
                <div class="fr-table-wrap">
                    <table class="soc-table {{ $showZero ? '' : 'soc-hide-zero' }}">
                        <thead>
                            <tr>
                                <th class="lbl">{{ __('Code') }}</th>
                                <th class="lbl">{{ __('Account') }}</th>
                                <th>{{ __('Opening') }} ({{ $cs }})</th>
                                <th>{{ __('Movement') }} ({{ $cs }})</th>
                                <th>{{ __('Closing') }} ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $item)
                                @php $zero = abs($item['movement']) <= 0 && abs($item['opening']) <= 0 && abs($item['closing']) <= 0; @endphp
                                <tr class="@if($zero) soc-zero @endif">
                                    <td class="ac"><span class="soc-code">{{ $item['account']->code }}</span></td>
                                    <td class="soc-name">{{ $item['account']->name }}</td>
                                    <td class="amt {{ $item['opening'] < 0 ? 'soc-neg' : '' }}">{{ $n($item['opening']) }}</td>
                                    <td class="amt {{ $item['movement'] < 0 ? 'soc-neg' : '' }}">{{ $n($item['movement']) }}</td>
                                    <td class="amt {{ $item['closing'] < 0 ? 'soc-neg' : '' }}">{{ $n($item['closing']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="soc-empty">{{ __('No equity accounts found.') }}</td>
                                </tr>
                            @endforelse
                            <tr class="soc-subtotal">
                                <td class="ac"><span class="soc-code"></span></td>
                                <td>{{ __('Net Income for the Period') }}</td>
                                <td class="amt"></td>
                                <td class="amt {{ $net_income < 0 ? 'soc-neg' : '' }}">{{ $n($net_income) }}</td>
                                <td class="amt"></td>
                            </tr>
                            <tr class="soc-total">
                                <td class="ac"><span class="soc-code"></span></td>
                                <td>{{ __('Total Equity') }}</td>
                                <td class="amt">{{ $n($total_opening) }}</td>
                                <td class="amt {{ ($total_closing - $total_opening) < 0 ? 'soc-neg' : '' }}">{{ $n($total_closing - $total_opening) }}</td>
                                <td class="amt">{{ $n($total_closing) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- co-foot — hidden on screen, shown in print -->
                <footer class="soc-co-foot">
                    <span>{{ $companyName }}{{ $branchLine ? ' · '.$branchLine : '' }}</span>
                    <span class="fr">{{ __('Statement of Changes in Equity') }} · <span class="soc-pageno"></span></span>
                </footer>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.soc-pageno').forEach(function (el) {
                el.textContent = 'Page 1 of 1';
            });
        </script>
    @endpush
</x-app-layout>