<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Rates') }}</h1>
                <p class="sub">{{ __('Every rate version per tax code, with its effective window.') }}</p>
            </div>
        </div>

        @include('accounting.taxation._tabs', ['active' => 'rates'])

        @include('accounting.taxation._create-rate')

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:800px;">
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
                                $today = today();
                                $from = $rate->effective_from instanceof \Illuminate\Support\Carbon ? $rate->effective_from : \Illuminate\Support\Carbon::parse($rate->effective_from);
                                $to = $rate->effective_to !== null
                                    ? ($rate->effective_to instanceof \Illuminate\Support\Carbon ? $rate->effective_to : \Illuminate\Support\Carbon::parse($rate->effective_to))
                                    : null;

                                if ($from->greaterThan($today)) {
                                    $rateState = ['tx-b-pend', __('Scheduled')];
                                } elseif ($to === null || $to->greaterThanOrEqualTo($today)) {
                                    $rateState = ['tx-b-ok', __('Active')];
                                } else {
                                    $rateState = ['tx-b-off', __('Expired')];
                                }
                            @endphp
                            <tr>
                                <td class="tx-mono tx-name">{{ $rate->taxCode?->code }}</td>
                                <td>{{ $rate->taxCode?->name }}</td>
                                <td class="num">{{ number_format((float) $rate->rate_pct, 2) }}</td>
                                <td>{{ Str::of($rate->taxCode?->treatment ?? '')->replace('_', ' ')->title() }}</td>
                                <td>{{ $from->format('d M Y') }}</td>
                                <td>{{ $to ? $to->format('d M Y') : __('open') }}</td>
                                <td><span class="tx-badge {{ $rateState[0] }}"><span class="bdot"></span>{{ $rateState[1] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No rates configured yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
