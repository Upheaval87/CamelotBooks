<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Periods') }}</h1>
                <p class="sub">{{ __('Filing calendar per tax type — prepare, file and track each period.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-ghost" disabled title="{{ __('Periods are generated automatically from your fiscal calendar.') }}">{{ __('Generate Periods') }}</button>
            </div>
        </div>

        @php
            $periodRows = $periods->getCollection();
            $openCount = $periodRows->filter(fn ($p) => $p->status === 'OPEN')->count();
            $unfiledCount = $periodRows->filter(fn ($p) => in_array($p->status, ['OPEN', 'IN_PREPARATION']) && $p->end_date->isPast())->count();
            $filedCount = $periodRows->filter(fn ($p) => ! is_null($p->filed_date))->count();
        @endphp

        <div class="tx-kpis" style="grid-template-columns:repeat(3, 1fr);">
            <div class="tx-kpi">
                <div class="l">{{ __('Open Periods') }}</div>
                <div class="v">{{ $openCount }}</div>
                <div class="n">{{ __('currently accepting transactions') }}</div>
            </div>
            <div class="tx-kpi {{ $unfiledCount > 0 ? 'warn' : '' }}">
                <div class="l">{{ __('Awaiting Preparation') }}</div>
                <div class="v">{{ $unfiledCount }}</div>
                <div class="n">{{ __('ended but not yet filed') }}</div>
            </div>
            <div class="tx-kpi">
                <div class="l">{{ __('Filed To Date') }}</div>
                <div class="v">{{ $filedCount }}</div>
                <div class="n">{{ __('returns submitted or closed') }}</div>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Start') }}</th>
                            <th>{{ __('End') }}</th>
                            <th>{{ __('Filing Due') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            @php
                                $tchipClass = match ($period->taxType?->category) {
                                    'WHT' => 'tx-t-wht',
                                    'PAYE' => 'tx-t-paye',
                                    'FBT' => 'tx-t-fbt',
                                    default => 'tx-t-vat',
                                };
                                $statusMap = [
                                    'OPEN' => ['tx-b-ok', __('Open')],
                                    'IN_PREPARATION' => ['tx-b-pend', __('In Preparation')],
                                    'SUBMITTED' => ['tx-b-post', __('Submitted')],
                                    'CLOSED' => ['tx-b-off', __('Closed')],
                                    'AMENDED' => ['tx-b-rev', __('Amended')],
                                ];
                                [$badgeClass, $badgeLabel] = $statusMap[$period->status] ?? ['tx-b-off', $period->status];
                            @endphp
                            <tr>
                                <td class="tx-name">{{ $period->label }}</td>
                                <td><span class="tx-tchip {{ $tchipClass }}">{{ $period->taxType?->name ?? '&mdash;' }}</span></td>
                                <td>{{ $period->start_date->format('d M Y') }}</td>
                                <td>{{ $period->end_date->format('d M Y') }}</td>
                                <td>{{ $period->filing_due_date?->format('d M Y') ?? '&mdash;' }}</td>
                                <td><span class="tx-badge {{ $badgeClass }}"><span class="bdot"></span>{{ $badgeLabel }}</span></td>
                                <td class="tx-mono tx-em">{{ $period->reference ?? '&mdash;' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No tax periods yet — they appear once your fiscal calendar generates them.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($periods instanceof \Illuminate\Pagination\LengthAwarePaginator || method_exists($periods, 'previousPageUrl'))
                <div class="tx-pag">
                    <div class="info">{{ $periods->firstItem() }}&ndash;{{ $periods->lastItem() }} of {{ $periods->total() }} periods</div>
                    <div style="display:flex;gap:6px;">
                        @if ($periods->previousPageUrl())
                            <a href="{{ $periods->previousPageUrl() }}" class="tx-btn tx-btn-ghost tx-btn-sm">&larr; {{ __('Prev') }}</a>
                        @endif
                        @if ($periods->nextPageUrl())
                            <a href="{{ $periods->nextPageUrl() }}" class="tx-btn tx-btn-ghost tx-btn-sm">{{ __('Next') }} &rarr;</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
