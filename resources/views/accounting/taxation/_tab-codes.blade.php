<div class="tx-card">
    <div class="tx-li-wrap">
        <table class="tx-table" style="min-width:860px;">
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th class="num">{{ __('Rate (%)') }}</th>
                    <th>{{ __('Treatment') }}</th>
                    <th>{{ __('Effective From') }}</th>
                    <th>{{ __('Effective To') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($codes as $code)
                    @php
                        $tchipClass = match ($code->taxType?->category) {
                            'WHT' => 'tx-t-wht',
                            'PAYE' => 'tx-t-paye',
                            'FBT' => 'tx-t-fbt',
                            default => 'tx-t-vat',
                        };
                    @endphp
                    <tr>
                        <td class="tx-mono tx-name">{{ $code->code }}</td>
                        <td>{{ $code->name }}</td>
                        <td><span class="tx-tchip {{ $tchipClass }}">{{ $code->taxType?->name ?? '&mdash;' }}</span></td>
                        @php $latestRate = $code->rates->first(); @endphp
                        <td class="num">{{ $latestRate ? number_format((float) $latestRate->rate_pct, 2) : '&mdash;' }}</td>
                        <td>{{ Str::of($code->treatment ?? '')->replace('_', ' ')->title() }}</td>
                        <td>{{ $code->effective_from?->format('d M Y') }}</td>
                        <td>{{ $code->effective_to?->format('d M Y') ?? __('open') }}</td>
                        <td>
                            @if ($code->active)
                                <span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Active') }}</span>
                            @else
                                <span class="tx-badge tx-b-off"><span class="bdot"></span>{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No tax codes configured yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
