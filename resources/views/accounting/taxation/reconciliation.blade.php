<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Reconciliation') }}</h1>
                <p class="sub">{{ __('Expected tax vs recorded tax vs GL — recalculated on every load.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-cta" disabled title="{{ __('Figures recalculate automatically every time this page loads.') }}">{{ __('Run Reconciliation') }}</button>
            </div>
        </div>

        @php
            $outOfBalance = $rows->contains(fn ($r) => abs((float) ($r['variance'] ?? 0)) > 0.005)
                || $rows->contains(fn ($r) => ! empty($r['report_variance']) && abs((float) $r['report_variance']) > 0.005);
        @endphp

        <div class="tx-card">
            <div class="tx-card-h">
                <span class="ic">&#9878;</span>
                <h2>{{ __('Tax Control Check') }} <span style="color:var(--muted);font-weight:600;">({{ $cs }})</span></h2>
                @if (! $outOfBalance)
                    <span class="tx-badge tx-b-ok" style="margin-left:auto;"><span class="bdot"></span>{{ __('In balance') }}</span>
                @else
                    <span class="tx-badge tx-b-rev" style="margin-left:auto;"><span class="bdot"></span>{{ __('Variance detected') }}</span>
                @endif
            </div>
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:1000px;">
                    <thead>
                        <tr>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Type / Side') }}</th>
                            <th class="num">{{ __('Expected') }}</th>
                            <th class="num">{{ __('Calculated') }}</th>
                            <th class="num">{{ __('Posted to GL') }}</th>
                            <th class="num">{{ __('GL Variance') }}</th>
                            <th class="num">{{ __('Reported') }}</th>
                            <th class="num">{{ __('Report Variance') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $rowVar = (float) ($row['variance'] ?? 0);
                                $repVar = (float) ($row['report_variance'] ?? 0);
                            @endphp
                            <tr>
                                <td class="tx-name">{{ $row['period_label'] }}</td>
                                <td><span class="tx-tchip tx-t-vat">{{ $row['display_label'] }}</span></td>
                                <td class="num">{{ number_format($row['expected'], 2) }}</td>
                                <td class="num">{{ number_format($row['calculated'], 2) }}</td>
                                <td class="num">{{ number_format($row['posted'], 2) }}</td>
                                <td class="num {{ abs($rowVar) > 0.005 ? 'tx-neg' : 'tx-green' }}">{{ number_format($rowVar, 2) }}</td>
                                <td class="num">{{ $row['reported'] !== null ? number_format($row['reported'], 2) : '&mdash;' }}</td>
                                <td class="num {{ abs($repVar) > 0.005 ? 'tx-neg' : 'tx-green' }}">{{ number_format($repVar, 2) }}</td>
                                <td class="tx-row-act">
                                    @if (! empty($row['working_paper_url']))
                                        <a class="tx-jl" href="{{ $row['working_paper_url'] }}">Working paper &rarr;</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No tax periods to reconcile yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="lbl">{{ __('Total') }}</td>
                            <td class="num lbl">{{ number_format($rows->sum('expected'), 2) }}</td>
                            <td class="num lbl">{{ number_format($rows->sum('calculated'), 2) }}</td>
                            <td class="num lbl">{{ number_format($rows->sum('posted'), 2) }}</td>
                            <td class="num lbl {{ ! $outOfBalance ? '' : 'tx-neg' }}">{{ number_format($rows->sum('variance'), 2) }}</td>
                            <td class="num lbl">{{ number_format($rows->sum(fn ($r) => (float) ($r['reported'] ?? 0)), 2) }}</td>
                            <td class="num lbl {{ ! $outOfBalance ? '' : 'tx-neg' }}">{{ number_format($rows->sum('report_variance'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tx-note">
            {{ __('A non-zero GL variance usually means a manual journal touched a tax control account without a matching document. Investigate before filing.') }}
        </div>
    </div>
</x-app-layout>
