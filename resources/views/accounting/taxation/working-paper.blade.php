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
            $netVariance = abs((float) ($summary['total_variance'] ?? 0)) > 0.005;
            $glVariance = abs(((float) ($summary['net_calculated'] ?? 0)) - ((float) ($summary['posted_gl'] ?? 0))) > 0.005;
        @endphp

        <div class="tx-opt-tag">{{ __('4') }} &middot; {{ __('VAT Return') }} &middot; {{ $period->label }}</div>

        <div class="tx-page-head">
            <div>
                <h1 style="display:flex;gap:10px;align-items:center">
                    {{ __('VAT Return') }}
                    <span class="tx-badge {{ $badgeClass }}"><span class="bdot"></span>{{ $badgeLabel }}</span>
                </h1>
                <p class="sub">{{ __('Tax period') }} {{ $period->label }} &middot; {{ __('auto-generated working paper') }}.</p>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.taxation.periods') }}" class="tx-btn tx-btn-ghost">{{ __('Export') }}</a>
                <button type="button" class="tx-btn tx-btn-cta" disabled>{{ __('Approve & File') }}</button>
            </div>
        </div>

        <div class="tx-grid2">
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#129534;</span>
                    <h2>{{ __('Return Working Paper') }}</h2>
                </div>
                <div class="tx-pad">
                    <div class="tx-sect-t">{{ __('Output Tax') }}</div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Taxable Sales') }}</span>
                        <span class="v">{{ number_format($summary['output_base'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Output VAT') }}</span>
                        <span class="v">{{ number_format($summary['output_tax'], 2) }}</span>
                    </div>

                    <div class="tx-sect-t">{{ __('Input Tax') }}</div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Taxable Purchases') }}</span>
                        <span class="v">{{ number_format($summary['input_base'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Input VAT') }}</span>
                        <span class="v">{{ number_format($summary['recoverable_input'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Adjustments') }}</span>
                        <span class="v">{{ number_format($summary['adjustments'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple" style="border-top:1.5px solid var(--deep-1,#17565d);margin-top:8px;padding-top:12px">
                        <span class="l" style="font-weight:800;color:var(--ink,#0B2A2D)">{{ __('Net VAT Payable') }}</span>
                        <span class="v">{{ number_format($summary['net_calculated'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic">&#128260;</span>
                    <h2>{{ __('Reconciliation Check') }}</h2>
                </div>
                <div class="tx-pad">
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Expected (GL)') }}</span>
                        <span class="v">{{ number_format($summary['net_expected'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Calculated (return)') }}</span>
                        <span class="v">{{ number_format($summary['net_calculated'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Posted') }}</span>
                        <span class="v">{{ number_format($summary['posted_gl'], 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Variance') }}</span>
                        <span class="v {{ $glVariance ? 'tx-red' : 'tx-green' }}">{{ number_format($summary['total_variance'] ?? 0, 2) }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Filing date') }}</span>
                        <span class="v">{{ $period->filing_due_date?->format('d M Y') ?? '&mdash;' }}</span>
                    </div>
                    <div class="tx-dl-simple">
                        <span class="l">{{ __('Reference') }}</span>
                        <span class="v tx-mono">VAT-{{ $period->start_date->format('Y-m') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
