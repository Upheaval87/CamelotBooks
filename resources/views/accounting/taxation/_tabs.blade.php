@php
    $active = $active ?? 'types';
    $txTabs = [
        'types' => ['Tax Types', route('accounting.taxation.config', ['tab' => 'types'])],
        'rates' => ['Tax Rates', route('accounting.taxation.config', ['tab' => 'rates'])],
        'codes' => ['Tax Codes', route('accounting.taxation.config', ['tab' => 'codes'])],
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
