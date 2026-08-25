@php
    $active = $active ?? 'types';
    $txTabs = [
        'types' => ['Tax Types', route('accounting.taxation.config', ['tab' => 'types'])],
        'codes' => ['Tax Codes', route('accounting.taxation.config', ['tab' => 'codes'])],
        'rates' => ['Tax Rates', route('accounting.taxation.config', ['tab' => 'rates'])],
        'exemptions' => ['Exemptions', route('accounting.taxation.config', ['tab' => 'exemptions'])],
        'jurisdictions' => ['Jurisdictions', route('accounting.taxation.config', ['tab' => 'jurisdictions'])],
        'accounts' => ['Tax Accounts', route('accounting.taxation.config', ['tab' => 'accounts'])],
    ];
@endphp
<div class="tx-tabs">
    @foreach ($txTabs as $key => [$label, $url])
        <a href="{{ $url }}" class="tx-tab {{ $key === $active ? 'on' : '' }}">{{ __($label) }}</a>
    @endforeach
</div>
