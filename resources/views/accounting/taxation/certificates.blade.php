<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Withholding Certificates') }}</h1>
                <p class="sub">{{ __('Certificates generated from supplier payments subject to withholding.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-ghost" onclick="window.txExportTable(this, 'wht-certificates')">Export</button>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th>{{ __('Certificate #') }}</th>
                            <th>{{ __('Supplier') }}</th>
                            <th>{{ __('Tax Code') }}</th>
                            <th class="num">{{ __('Gross') }}</th>
                            <th class="num">{{ __('Rate %') }}</th>
                            <th class="num">{{ __('WHT Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="tx-row-act"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certificates as $certificate)
                            <tr>
                                <td class="tx-mono tx-name">{{ $certificate->cert_number }}</td>
                                <td>{{ $certificate->supplier?->name ?? '&mdash;' }}</td>
                                <td><span class="tx-tchip tx-t-wht">{{ $certificate->taxCode?->code ?? '&mdash;' }}</span></td>
                                <td class="num">{{ number_format((float) $certificate->gross, 2) }}</td>
                                <td class="num">{{ number_format((float) $certificate->rate_pct, 2) }}</td>
                                <td class="num"><strong>{{ number_format((float) $certificate->wht_amount, 2) }}</strong></td>
                                <td>
                                    @php [$badgeClass, $badgeLabel] = match ($certificate->status) {
                                        'ISSUED' => ['tx-b-ok', __('Issued')],
                                        'DRAFT', null => ['tx-b-pend', __('Draft')],
                                        default => ['tx-b-off', ucfirst(strtolower($certificate->status))],
                                    };
                                @endphp
                                    <span class="tx-badge {{ $badgeClass }}"><span class="bdot"></span>{{ $badgeLabel }}</span>
                                </td>
                                <td class="tx-row-act">
                                    <button type="button" class="tx-ibtn" disabled title="{{ __('Certificate printing arrives with the returns workflow.') }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No withholding certificates yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
