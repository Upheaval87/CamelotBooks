<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-opt-tag">{{ __('2') }} &middot; {{ __('PAYE & Withholding Registers') }}</div>

        <div class="tx-page-head">
            <div>
                <h1>{{ __('PAYE & Withholding Registers') }}</h1>
                <p class="sub">{{ __('Employee PAYE deductions and supplier withholding tax transactions.') }}</p>
            </div>
        </div>

        <div class="tx-grid2">
            {{-- PAYE Register --}}
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#128188;</span>
                    <h2>{{ __('PAYE Register') }}</h2>
                    <span style="margin-left:auto;font-weight:800;color:var(--ink,#0B2A2D);font-variant-numeric:tabular-nums">{{ number_format($payeTotal, 2) }}</span>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:500px">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Tax Code') }}</th>
                                <th class="num">{{ __('Base') }}</th>
                                <th class="num">{{ __('Tax') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payeTransactions as $txn)
                                <tr>
                                    <td class="tx-em">{{ $txn->created_at?->format('d M Y') ?? '&mdash;' }}</td>
                                    <td><span class="tx-tchip tx-t-paye">{{ $txn->taxCode?->code ?? '&mdash;' }}</span></td>
                                    <td class="num">{{ number_format((float) $txn->base_amount, 2) }}</td>
                                    <td class="num">{{ number_format((float) $txn->tax_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No PAYE transactions recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if ($payeTransactions->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="font-weight:800">{{ __('Total') }}</td>
                                    <td class="num" style="font-weight:800">{{ number_format($payeTransactions->sum(fn ($t) => (float) $t->base_amount), 2) }}</td>
                                    <td class="num" style="font-weight:800">{{ number_format($payeTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- WHT Register --}}
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#128196;</span>
                    <h2>{{ __('WHT Register') }}</h2>
                    <span style="margin-left:auto;font-weight:800;color:var(--ink,#0B2A2D);font-variant-numeric:tabular-nums">{{ number_format($whtTotal, 2) }}</span>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:500px">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Tax Code') }}</th>
                                <th class="num">{{ __('Base') }}</th>
                                <th class="num">{{ __('Tax') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($whtTransactions as $txn)
                                <tr>
                                    <td class="tx-em">{{ $txn->created_at?->format('d M Y') ?? '&mdash;' }}</td>
                                    <td><span class="tx-tchip tx-t-wht">{{ $txn->taxCode?->code ?? '&mdash;' }}</span></td>
                                    <td class="num">{{ number_format((float) $txn->base_amount, 2) }}</td>
                                    <td class="num">{{ number_format((float) $txn->tax_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No WHT transactions recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if ($whtTransactions->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="font-weight:800">{{ __('Total') }}</td>
                                    <td class="num" style="font-weight:800">{{ number_format($whtTransactions->sum(fn ($t) => (float) $t->base_amount), 2) }}</td>
                                    <td class="num" style="font-weight:800">{{ number_format($whtTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- WHT Certificates --}}
        @if ($certificates->isNotEmpty())
            <div class="tx-card" style="margin-top:16px">
                <div class="tx-card-h">
                    <span class="ic">&#128196;</span>
                    <h2>{{ __('Withholding Tax Certificates') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Certificate') }}</th>
                                <th>{{ __('Vendor') }}</th>
                                <th class="num">{{ __('Rate (%)') }}</th>
                                <th class="num">{{ __('Gross') }}</th>
                                <th class="num">{{ __('WHT') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($certificates as $cert)
                                <tr>
                                    <td class="tx-mono">{{ $cert->certificate_number ?? '&mdash;' }}</td>
                                    <td class="tx-name">{{ $cert->vendor?->name ?? '&mdash;' }}</td>
                                    <td class="num">{{ number_format((float) $cert->rate_pct, 2) }}</td>
                                    <td class="num">{{ number_format((float) $cert->gross_amount, 2) }}</td>
                                    <td class="num">{{ number_format((float) $cert->wht_amount, 2) }}</td>
                                    <td>
                                        @if (($cert->status ?? '') === 'ISSUED')
                                            <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Issued') }}</span>
                                        @else
                                            <span class="tx-badge tx-b-pend"><span class="bdot"></span>{{ __('Draft') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No certificates yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
