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
        @endphp

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Dashboard') }}</h1>
                <p class="sub">{{ __('Live tax position across VAT, withholding and payroll.') }}</p>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.taxation.periods') }}" class="tx-btn tx-btn-ghost">{{ __('Tax Periods') }}</a>
                <a href="{{ $prepareUrl }}" class="tx-btn tx-btn-cta">{{ __('Prepare Return') }}</a>
            </div>
        </div>

        <div class="tx-kpis">
            <div class="tx-kpi hero">
                <div class="l">{{ __('VAT Payable') }}</div>
                <div class="v">{{ number_format($kpi['net_payable'], 0) }}</div>
                <div class="n">
                    @if ($heroDue)
                        {{ \Illuminate\Support\Carbon::parse($heroDue)->format('M Y') }} &middot; {{ __('due :date', ['date' => \Illuminate\Support\Carbon::parse($heroDue)->format('d M')]) }}
                    @else
                        {{ __('No deadline') }}
                    @endif
                </div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Output Tax') }}</div>
                <div class="v">{{ number_format($kpi['output_tax'], 0) }}</div>
                <div class="n">
                    @if ($currentPeriod)
                        {{ $currentPeriod->start_date->format('M Y') }}
                    @endif
                </div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Input Tax') }}</div>
                <div class="v">{{ number_format($kpi['input_tax'], 0) }}</div>
                <div class="n">
                    @if ($currentPeriod)
                        {{ $currentPeriod->start_date->format('M Y') }}
                    @endif
                </div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Outstanding') }}</div>
                <div class="v {{ $kpi['outstanding'] > 0 ? 'tx-neg' : '' }}">{{ number_format($kpi['outstanding'], 0) }}</div>
                <div class="n">{{ number_format($kpi['paid'], 0) }} {{ __('paid to date') }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Current Period') }}</div>
                <div class="v" style="font-size:15px">
                    @if ($currentPeriod)
                        {{ $currentPeriod->label }}
                    @else
                        &mdash;
                    @endif
                </div>
                <div class="n" style="color:var(--green,#15803d)">
                    @if ($currentPeriod)
                        {{ __('Open') }}
                    @else
                        &mdash;
                    @endif
                </div>
            </div>
        </div>

        <div class="tx-grid2">
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#128202;</span>
                    <h2>{{ __('Input vs Output VAT') }} &mdash; {{ __('last 6 months') }}</h2>
                </div>
                <div class="tx-pad">
                    @if (count($chartMonths) > 0)
                        <div class="tx-chart">
                            @foreach ($chartMonths as $monthData)
                                <div class="tx-cg">
                                    <div class="tx-cb">
                                        <div class="tx-bar in" title="{{ __('Input') }}: {{ number_format($monthData['in'], 2) }}" style="height:{{ max(3, (int) round($monthData['in'] / $maxBar * 110)) }}px;"></div>
                                        <div class="tx-bar out" title="{{ __('Output') }}: {{ number_format($monthData['out'], 2) }}" style="height:{{ max(3, (int) round($monthData['out'] / $maxBar * 110)) }}px;"></div>
                                    </div>
                                    <span class="tx-cl">{{ $monthData['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="tx-legend">
                            <span><i style="background:rgba(18,143,142,.55);"></i>{{ __('Input VAT') }}</span>
                            <span><i style="background:var(--deep-2,#0c3539);"></i>{{ __('Output VAT') }}</span>
                        </div>
                    @else
                        <p class="tx-em" style="padding:24px 0;text-align:center;">{{ __('No posted tax transactions yet.') }}</p>
                    @endif
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="tx-card" style="margin-bottom:0">
                    <div class="tx-card-h">
                        <span class="ic">&#9200;</span>
                        <h2>{{ __('Upcoming Filing Deadlines') }}</h2>
                    </div>
                    <div class="tx-pad">
                        @forelse ($kpi['upcoming_deadlines'] as $deadline)
                            <div class="tx-dl-simple">
                                <span class="l">{{ $deadline['tax_type_name'] ?? $deadline['tax_type_code'] }} &middot; {{ $deadline['label'] }}</span>
                                <span class="v">{{ \Illuminate\Support\Carbon::parse($deadline['filing_due_date'])->format('d M Y') }}</span>
                            </div>
                        @empty
                            <p class="tx-em" style="text-align:center;padding:12px 0;">{{ __('No upcoming deadlines.') }}</p>
                        @endforelse
                        @if ($kpi['unfiled_periods'] > 0)
                            <div class="tx-dl-simple">
                                <span class="l">{{ __('Unfiled periods') }}</span>
                                <span class="v tx-red">{{ $kpi['unfiled_periods'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="tx-card">
                    <div class="tx-card-h">
                        <span class="ic">&#9888;</span>
                        <h2>{{ __('Tax Exceptions') }}</h2>
                    </div>
                    <div class="tx-pad">
                        @forelse ($exceptions as $exception)
                            <div class="tx-dl-simple">
                                <span class="l">{{ $exception['message'] }}</span>
                                <span class="v tx-red">&times;</span>
                            </div>
                        @empty
                            <div class="tx-dl-simple">
                                <span class="l">{{ __('No exceptions') }}</span>
                                <span class="v" style="color:var(--green,#15803d)">&check;</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
