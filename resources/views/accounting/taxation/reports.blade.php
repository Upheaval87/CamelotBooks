<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        @php
            $reportMap = [
                'vat_transaction_report' => ['&#129534;', __('VAT Transaction Report')],
                'vat_input_report'       => ['&#11015;', __('VAT Input Report')],
                'vat_output_report'      => ['&#11014;', __('VAT Output Report')],
                'vat_return_summary'     => ['&#128211;', __('VAT Return Summary')],
                'vat_reconciliation'     => ['&#128257;', __('VAT Reconciliation')],
                'vat_audit_trail'        => ['&#128373;', __('VAT Audit Trail')],
                'tax_liability_report'   => ['&#128176;', __('Tax Liability Report')],
                'wht_report'             => ['&#129517;', __('Withholding Tax Report')],
                'wht_certificates'       => ['&#128220;', __('WHT Certificates')],
                'tax_exemption_report'   => ['&#128683;', __('Tax Exemption Report')],
                'zero_rated_sales'       => ['&#48;%', __('Zero-Rated Sales')],
                'taxable_purchases'      => ['&#128722;', __('Taxable Purchases')],
                'taxable_sales'          => ['&#128717;', __('Taxable Sales')],
                'tax_adjustments'        => ['&#128736;', __('Tax Adjustments')],
                'tax_account_ledger'     => ['&#128213;', __('Tax Account Ledger')],
                'tax_period_summary'     => ['&#128197;', __('Tax Period Summary')],
                'tax_payable_receivable' => ['&#9878;', __('Tax Payable / Receivable')],
                'tax_transaction_register' => ['&#128218;', __('Tax Transaction Register')],
                'tax_exception_report'   => ['&#9888;', __('Tax Exception Report')],
                'tax_audit_report'       => ['&#128272;', __('Tax Audit Report')],
            ];
        @endphp

        <div class="tx-opt-tag">{{ __('7') }} &middot; {{ __('Tax Reports') }} ({{ __('A &middot; D') }})</div>

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Reports') }}</h1>
                <p class="sub">{{ __('Statutory and management tax reporting.') }}</p>
            </div>
        </div>

        @if ($reports->isEmpty())
            <div class="tx-card">
                <div class="tx-pad" style="text-align:center;padding:48px 24px;">
                    <p style="color:var(--sub,#41585c);font-size:13.5px;">{{ __('No tax reports are available for your permissions yet.') }}</p>
                </div>
            </div>
        @else
            <div class="tx-tiles">
                @foreach ($reports as $report)
                    @php
                        [$defaultIcon, $defaultLabel] = $reportMap[$report['key']] ?? ['&#128196;', $report['name']];
                    @endphp
                    <a href="{{ $report['url'] }}" class="tx-tile" title="{{ $report['description'] }}">
                        <span class="ic">{!! $defaultIcon !!}</span>
                        <span>{{ $defaultLabel }}</span>
                        <em>&rarr;</em>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
