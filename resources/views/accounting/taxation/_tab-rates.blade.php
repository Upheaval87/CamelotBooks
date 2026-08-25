@php
    $activeCount = 0;
    $scheduledCount = 0;
    $expiredCount = 0;
    $today = \Illuminate\Support\Carbon::now();
@endphp

<div class="tx-card">
    <div class="tx-card-h">
        <span class="ic">&#128200;</span>
        <h2>{{ __('Tax Rates') }}</h2>
    </div>
    <div class="tx-li-wrap">
        <table class="tx-table" style="min-width:860px;">
            <thead>
                <tr>
                    <th>{{ __('Tax Code') }}</th>
                    <th>{{ __('Code Name') }}</th>
                    <th class="num">{{ __('Rate (%)') }}</th>
                    <th>{{ __('Treatment') }}</th>
                    <th>{{ __('Effective From') }}</th>
                    <th>{{ __('Effective To') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rates as $rate)
                    @php
                        $from = $rate->effective_from instanceof \Illuminate\Support\Carbon ? $rate->effective_from : \Illuminate\Support\Carbon::parse($rate->effective_from);
                        $to = $rate->effective_to !== null
                            ? ($rate->effective_to instanceof \Illuminate\Support\Carbon ? $rate->effective_to : \Illuminate\Support\Carbon::parse($rate->effective_to))
                            : null;

                        if ($from->greaterThan($today)) {
                            $rateState = ['tx-b-pend', __('Scheduled')];
                            $scheduledCount++;
                        } elseif ($to === null || $to->greaterThanOrEqualTo($today)) {
                            $rateState = ['tx-b-ok', __('Active')];
                            $activeCount++;
                        } else {
                            $rateState = ['tx-b-off', __('Expired')];
                            $expiredCount++;
                        }
                    @endphp
                    <tr>
                        <td class="tx-mono tx-name">{{ $rate->taxCode?->code }}</td>
                        <td class="tx-name">{{ $rate->taxCode?->name }}</td>
                        <td class="num">{{ number_format((float) $rate->rate_pct, 2) }}</td>
                        <td class="tx-em">{{ Str::of($rate->taxCode?->treatment ?? '')->replace('_', ' ')->title() }}</td>
                        <td class="tx-em">{{ $from->format('d M Y') }}</td>
                        <td class="tx-em">{{ $to ? $to->format('d M Y') : __('open') }}</td>
                        <td><span class="tx-badge {{ $rateState[0] }}"><span class="bdot"></span>{{ $rateState[1] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="tx-em" style="text-align:center;padding:36px;">{{ __('No rates configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
