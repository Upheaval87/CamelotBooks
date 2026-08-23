<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        @php
            $preparePeriod = $periods->first(fn ($p) => $p->end_date->isPast() && in_array($p->status, ['OPEN', 'IN_PREPARATION']))
                ?? $periods->first();
            $prepareUrl = $preparePeriod
                ? route('accounting.taxation.returns.working-paper', ['periodId' => $preparePeriod->id])
                : route('accounting.taxation.periods');
            $firstDeadline = collect($kpi['upcoming_deadlines'])->first();
            $heroDue = $firstDeadline['filing_due_date'] ?? null;
            $currentPeriod = $periods->first();

            $months = [];
            foreach ($periods as $chartPeriod) {
                $key = $chartPeriod->start_date->format('Y-m');
                $months[$key] ??= ['label' => $chartPeriod->start_date->format('M'), 'out' => 0.0, 'in' => 0.0];
                $months[$key]['out'] += (float) $chartPeriod->output_tax;
                $months[$key]['in'] += (float) $chartPeriod->input_tax;
            }
            ksort($months);
            $chartMonths = array_slice($months, -6);
            $maxBar = 0.01;
            foreach ($chartMonths as $monthData) {
                $maxBar = max($maxBar, $monthData['out'], $monthData['in']);
            }

            $sevClass = ['error' => 'tx-b-rev', 'warning' => 'tx-b-pend', 'info' => 'tx-b-post'];
            $sevLabel = ['error' => 'Error', 'warning' => 'Warning', 'info' => 'Info'];
        @endphp

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Dashboard') }}</h1>
                <p class="sub">{{ __('Live tax position across VAT, withholding and payroll.') }}</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('accounting.taxation.periods') }}" class="tx-btn tx-btn-ghost">{{ __('Tax Periods') }}</a>
                <a href="{{ $prepareUrl }}" class="tx-btn tx-btn-cta">{{ __('Prepare Return') }}</a>
            </div>
        </div>

        <div class="tx-kpis">
            <div class="tx-kpi hero">
                <div class="l">{{ __('Net VAT Payable') }}</div>
                <div class="v">{{ number_format($kpi['net_payable'], 2) }}</div>
                <div class="n">
                    @if ($heroDue)
                        {{ __('Due :date', ['date' => \Illuminate\Support\Carbon::parse($heroDue)->format('d M Y')]) }}
                    @else
                        {{ __('No filing deadline scheduled') }}
                    @endif
                </div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Output Tax') }}</div>
                <div class="v">{{ number_format($kpi['output_tax'], 2) }}</div>
                <div class="n">({{ $cs }}) {{ __('charged on sales') }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Input Tax') }}</div>
                <div class="v">{{ number_format($kpi['input_tax'], 2) }}</div>
                <div class="n">({{ $cs }}) {{ __('recoverable on purchases') }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Outstanding') }}</div>
                <div class="v {{ $kpi['outstanding'] > 0 ? 'tx-neg' : 'tx-green' }}">{{ number_format($kpi['outstanding'], 2) }}</div>
                <div class="n">{{ __(':amount paid to date', ['amount' => number_format($kpi['paid'], 2)]) }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Current Period') }}</div>
                <div class="v" style="font-size:1rem;">
                    @if ($currentPeriod)
                        {{ $currentPeriod->label }}
                    @else
                        &mdash;
                    @endif
                </div>
                <div class="n">
                    @if ($currentPeriod)
                        {{ $currentPeriod->taxType?->name }} &middot; {{ __('ends :date', ['date' => $currentPeriod->end_date->format('d M')]) }}
                    @else
                        {{ __(':open open / :unfiled unfiled periods', ['open' => $kpi['open_periods'], 'unfiled' => $kpi['unfiled_periods']]) }}
                    @endif
                </div>
            </div>
        </div>

        <div class="tx-grid2">
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#9632;</span>
                    <h2>{{ __('Input vs Output Tax') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
                </div>
                <div class="tx-pad">
                    @if (count($chartMonths) > 0)
                        <div class="tx-chart">
                            @foreach ($chartMonths as $monthData)
                                <div class="tx-cg">
                                    <div class="tx-cb">
                                        <div class="tx-bar out" title="{{ __('Output') }}: {{ number_format($monthData['out'], 2) }}" style="height:{{ max(3, (int) round($monthData['out'] / $maxBar * 110)) }}px;"></div>
                                        <div class="tx-bar in" title="{{ __('Input') }}: {{ number_format($monthData['in'], 2) }}" style="height:{{ max(3, (int) round($monthData['in'] / $maxBar * 110)) }}px;"></div>
                                    </div>
                                    <span class="tx-cl">{{ $monthData['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="tx-legend">
                            <span><i style="background:var(--deep-2);"></i>{{ __('Output tax') }}</span>
                            <span><i style="background:rgba(18,143,142,.55);"></i>{{ __('Input tax') }}</span>
                        </div>
                    @else
                        <p class="tx-em" style="padding:24px 0;text-align:center;">{{ __('No posted tax transactions yet.') }}</p>
                    @endif
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="tx-card">
                    <div class="tx-card-h">
                        <span class="ic">&#9200;</span>
                        <h2>{{ __('Upcoming Deadlines') }}</h2>
                    </div>
                    <div class="tx-pad" style="display:flex;flex-direction:column;gap:10px;">
                        @forelse ($kpi['upcoming_deadlines'] as $deadline)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:9px;border-bottom:1px solid var(--hair, #EEF3F1);">
                                <div style="min-width:0;">
                                    <div class="tx-name" style="font-size:12.5px;">{{ $deadline['label'] }}</div>
                                    <div class="tx-em" style="font-size:11px;">{{ $deadline['tax_type_name'] ?? $deadline['tax_type_code'] }} &middot; {{ \Illuminate\Support\Carbon::parse($deadline['filing_due_date'])->format('d M Y') }}</div>
                                </div>
                                <span class="tx-badge {{ $deadline['days_left'] <= 7 ? 'tx-b-rev' : ($deadline['days_left'] <= 14 ? 'tx-b-pend' : 'tx-b-post') }}">
                                    <span class="bdot"></span>{{ $deadline['days_left'] }}d
                                </span>
                            </div>
                        @empty
                            <p class="tx-em" style="text-align:center;padding:12px 0;">{{ __('No upcoming deadlines.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="tx-card">
                    <div class="tx-card-h">
                        <span class="ic">&#9878;</span>
                        <h2>{{ __('Tax Exceptions') }}</h2>
                        @if (count($exceptions) > 0)
                            <span class="tx-badge tx-b-pend" style="margin-left:auto;"><span class="bdot"></span>{{ count($exceptions) }}</span>
                        @endif
                    </div>
                    <div class="tx-pad" style="display:flex;flex-direction:column;gap:12px;">
                        @forelse ($exceptions as $exception)
                            <div>
                                <span class="tx-badge {{ $sevClass[$exception['severity']] ?? 'tx-b-off' }}"><span class="bdot"></span>{{ $sevLabel[$exception['severity']] ?? ucfirst($exception['severity']) }}</span>
                                <p style="font-size:12px;color:var(--sub, #41585c);margin-top:6px;line-height:1.45;">{{ $exception['message'] }}</p>
                                <a class="tx-jl" style="font-size:11.5px;" href="{{ $exception['link'] }}">{{ __('View') }} &rarr;</a>
                            </div>
                        @empty
                            <p class="tx-em" style="text-align:center;padding:12px 0;">{{ __('All clear — no tax exceptions detected.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
