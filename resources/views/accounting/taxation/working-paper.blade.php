<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        @php
            [$badgeClass, $badgeLabel] = match ($period->status) {
                'OPEN' => ['tx-b-ok', __('Open')],
                'IN_PREPARATION' => ['tx-b-pend', __('In Preparation')],
                'SUBMITTED' => ['tx-b-post', __('Submitted')],
                'CLOSED' => ['tx-b-off', __('Closed')],
                'AMENDED' => ['tx-b-rev', __('Amended')],
                default => ['tx-b-off', $period->status],
            };
            $outputRows = collect($transactions)->where('side', 'OUTPUT')->values();
            $inputRows = collect($transactions)->where('side', 'INPUT')->values();
            $netVariance = abs((float) ($summary['total_variance'] ?? 0)) > 0.005;
            $glVariance = abs(((float) ($summary['net_calculated'] ?? 0)) - ((float) ($summary['posted_gl'] ?? 0))) > 0.005;
        @endphp

        <div class="tx-page-head">
            <div>
                <h1>{{ $period->label }} &mdash; {{ $period->taxType?->name }}</h1>
                <p class="sub">{{ __('Working paper') }} &middot; {{ $period->start_date->format('d M Y') }} &ndash; {{ $period->end_date->format('d M Y') }}
                    <span class="tx-badge {{ $badgeClass }}" style="margin-left:8px;"><span class="bdot"></span>{{ $badgeLabel }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('accounting.taxation.periods') }}" class="tx-btn tx-btn-ghost">{{ __('Back to Periods') }}</a>
                <button type="button" class="tx-btn tx-btn-ghost" onclick="window.txExportTable(this, 'working-paper-{{ strtolower($period->label) }}')">Export</button>
                <button type="button" class="tx-btn tx-btn-cta" disabled title="{{ __('Filing actions arrive with the returns workflow.') }}">{{ __('Approve & File') }}</button>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#9776;</span>
                <h2>{{ __('Summary') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
            </div>
            <dl class="tx-dl" style="padding:16px 20px;">
                <dt>{{ __('Output base') }}</dt><dd class="num">{{ number_format($summary['output_base'], 2) }}</dd>
                <dt>{{ __('Input base') }}</dt><dd class="num">{{ number_format($summary['input_base'], 2) }}</dd>
                <dt>{{ __('Output tax expected') }}</dt><dd class="num">{{ number_format($summary['output_expected'], 2) }}</dd>
                <dt>{{ __('Input tax expected') }}</dt><dd class="num">{{ number_format($summary['input_expected'], 2) }}</dd>
                <dt>{{ __('Output tax recorded') }}</dt><dd class="num">{{ number_format($summary['output_tax'], 2) }}</dd>
                <dt>{{ __('Recoverable input tax') }}</dt><dd class="num">{{ number_format($summary['recoverable_input'], 2) }}</dd>
                <dt>{{ __('Adjustments') }}</dt><dd class="num">{{ number_format($summary['adjustments'], 2) }}</dd>
                <dt>{{ __('Net tax payable') }}</dt><dd class="num"><strong>{{ number_format($summary['net_calculated'], 2) }}</strong></dd>
            </dl>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#9632;</span>
                <h2>{{ __('Output Tax') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
            </div>
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:860px;">
                    <thead>
                        <tr>
                            <th>{{ __('Tax Code') }}</th>
                            <th>{{ __('Treatment') }}</th>
                            <th class="num">{{ __('Base Amount') }}</th>
                            <th class="num">{{ __('Rate %') }}</th>
                            <th class="num">{{ __('Expected') }}</th>
                            <th class="num">{{ __('Calculated') }}</th>
                            <th class="num">{{ __('Variance') }}</th>
                            <th class="num">{{ __('Txns') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($outputRows as $row)
                            <tr>
                                <td class="tx-name"><span class="tx-mono">{{ $row['code'] }}</span> &middot; {{ $row['code_name'] }}</td>
                                <td>{{ Str::of($row['treatment'])->replace('_', ' ')->title() }}</td>
                                <td class="num">{{ number_format($row['base_amount'], 2) }}</td>
                                <td class="num">{{ number_format((float) $row['rate_pct'], 2) }}</td>
                                <td class="num">{{ number_format($row['expected_tax'], 2) }}</td>
                                <td class="num">{{ number_format($row['calculated_tax'], 2) }}</td>
                                <td class="num {{ abs((float) $row['variance']) > 0.005 ? 'tx-neg' : 'tx-green' }}">{{ number_format($row['variance'], 2) }}</td>
                                <td class="num">{{ $row['transaction_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--muted);">{{ __('No output-tax transactions in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="lbl">{{ __('Total') }}</td>
                            <td class="num lbl">{{ number_format(collect($outputRows)->sum('expected_tax'), 2) }}</td>
                            <td class="num lbl">{{ number_format(collect($outputRows)->sum('calculated_tax'), 2) }}</td>
                            <td class="num lbl {{ $netVariance ? 'tx-neg' : '' }}">{{ number_format(collect($outputRows)->sum('variance'), 2) }}</td>
                            <td class="num lbl">{{ collect($outputRows)->sum('transaction_count') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#9632;</span>
                <h2>{{ __('Input Tax') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
            </div>
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:860px;">
                    <thead>
                        <tr>
                            <th>{{ __('Tax Code') }}</th>
                            <th>{{ __('Treatment') }}</th>
                            <th class="num">{{ __('Base Amount') }}</th>
                            <th class="num">{{ __('Rate %') }}</th>
                            <th class="num">{{ __('Expected') }}</th>
                            <th class="num">{{ __('Calculated') }}</th>
                            <th class="num">{{ __('Variance') }}</th>
                            <th class="num">{{ __('Txns') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inputRows as $row)
                            <tr>
                                <td class="tx-name"><span class="tx-mono">{{ $row['code'] }}</span> &middot; {{ $row['code_name'] }}</td>
                                <td>{{ Str::of($row['treatment'])->replace('_', ' ')->title() }}</td>
                                <td class="num">{{ number_format($row['base_amount'], 2) }}</td>
                                <td class="num">{{ number_format((float) $row['rate_pct'], 2) }}</td>
                                <td class="num">{{ number_format($row['expected_tax'], 2) }}</td>
                                <td class="num">{{ number_format($row['calculated_tax'], 2) }}</td>
                                <td class="num {{ abs((float) $row['variance']) > 0.005 ? 'tx-neg' : 'tx-green' }}">{{ number_format($row['variance'], 2) }}</td>
                                <td class="num">{{ $row['transaction_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--muted);">{{ __('No input-tax transactions in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="lbl">{{ __('Total') }}</td>
                            <td class="num lbl">{{ number_format(collect($inputRows)->sum('expected_tax'), 2) }}</td>
                            <td class="num lbl">{{ number_format(collect($inputRows)->sum('calculated_tax'), 2) }}</td>
                            <td class="num lbl {{ $netVariance ? 'tx-neg' : '' }}">{{ number_format(collect($inputRows)->sum('variance'), 2) }}</td>
                            <td class="num lbl">{{ collect($inputRows)->sum('transaction_count') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tx-strip">
            <div>
                <span class="l">{{ __('Net Payable') }}</span>
                <span class="v">{{ number_format($summary['net_calculated'], 2) }}</span>
                <span class="n">({{ $cs }})</span>
            </div>
            <div>
                <span class="l">{{ __('Expected Net') }}</span>
                <span class="v">{{ number_format($summary['net_expected'], 2) }}</span>
                <span class="n">({{ $cs }})</span>
            </div>
            <div>
                <span class="l">{{ __('Total Variance') }}</span>
                <span class="v {{ $netVariance ? 'tx-neg' : 'tx-green' }}">{{ number_format($summary['total_variance'], 2) }}</span>
                <span class="n">({{ $cs }})</span>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#10003;</span>
                <h2>{{ __('Reconciliation Check') }}</h2>
            </div>
            <div class="tx-pad" style="display:flex;gap:24px;flex-wrap:wrap;">
                <div>
                    <div class="tx-em" style="font-size:11px;">{{ __('Net per working paper') }}</div>
                    <strong>{{ number_format($summary['net_calculated'], 2) }}</strong> <span class="tx-em">({{ $cs }})</span>
                </div>
                <div>
                    <div class="tx-em" style="font-size:11px;">{{ __('Posted to GL') }}</div>
                    <strong>{{ number_format($summary['posted_gl'], 2) }}</strong> <span class="tx-em">({{ $cs }})</span>
                </div>
                <div>
                    <div class="tx-em" style="font-size:11px;">{{ __('Status') }}</div>
                    @if (! $glVariance)
                        <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('In balance') }}</span>
                    @else
                        <span class="tx-badge tx-b-rev"><span class="bdot"></span>{{ __('Out by') }} {{ number_format($summary['net_calculated'] - $summary['posted_gl'], 2) }}</span>
                    @endif
                </div>
            </div>
            @if ($glVariance)
                <p class="tx-exc">{{ __('The tax control accounts do not agree with this working paper. Investigate unposted entries or manual adjustments before filing.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
