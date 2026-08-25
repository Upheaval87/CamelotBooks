<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        @php
            $tabLabels = [
                'types' => __('Tax Types'),
                'codes' => __('Tax Codes'),
                'rates' => __('Tax Rates'),
                'exemptions' => __('Exemptions'),
                'jurisdictions' => __('Jurisdictions'),
                'accounts' => __('Tax Accounts'),
            ];
        @endphp

        <div class="tx-opt-tag">{{ __('Tax Configuration') }} &middot; {{ $tabLabels[$activeTab] ?? '' }}</div>

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Configuration') }}</h1>
                <p class="sub">{{ __('Tax types, rates, codes, exemptions, jurisdictions and GL account mappings.') }}</p>
            </div>
        </div>

        @include('accounting.taxation._tabs', ['active' => $activeTab])

        @if ($activeTab === 'types')
            @include('accounting.taxation._tab-types')
        @elseif ($activeTab === 'rates')
            @include('accounting.taxation._tab-rates')
        @elseif ($activeTab === 'codes')
            @include('accounting.taxation._tab-codes')
        @elseif ($activeTab === 'exemptions')
            @include('accounting.taxation._tab-exemptions')
        @elseif ($activeTab === 'jurisdictions')
            @include('accounting.taxation._tab-jurisdictions')
        @elseif ($activeTab === 'accounts')
            @include('accounting.taxation._tab-accounts')
        @endif
    </div>
</x-app-layout>
