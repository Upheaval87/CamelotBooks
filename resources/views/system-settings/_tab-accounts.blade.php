<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'accounts'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="accounts-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Account Mappings') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-account-mappings') }}" id="accounts-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <h2>{{ __('Default Account Mappings') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Map system operations to your Chart of Accounts. Every journal entry posted by the system uses these mappings. If a mapping is empty, the relevant operation will fail until one is assigned.</p>

            @php
                $requiredKeys = ['sales_revenue', 'accounts_receivable', 'inventory', 'cost_of_goods_sold', 'accounts_payable', 'cash_on_hand'];
            @endphp
            @foreach(\App\Models\DefaultAccountMapping::availableKeys() as $key => $label)
                @php
                    $currentVal = $mappings[$key] ?? null;
                    $isRequired = in_array($key, $requiredKeys);
                    $isMapped = !is_null($currentVal);
                    $mappedAccount = $currentVal ? $accounts->firstWhere('id', (int) $currentVal) : null;
                @endphp
                <div class="map-row">
                    <div>
                        <span class="map-label">{{ $label }}</span>
                        <span class="map-key">{{ $key }}</span>
                    </div>
                    <div>
                        <x-scoped-search-field
                            name="{{ $key }}"
                            entity="account"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                            :value="$currentVal"
                            :label="$mappedAccount ? ($mappedAccount->code . ' - ' . $mappedAccount->name) : ''"
                            placeholder="{{ __('— Not mapped —') }}"
                        />
                        @if($isMapped)
                            <p class="mapping-ok">Mapped</p>
                        @elseif($isRequired)
                            <p class="mapping-warn">Required — will block journal posting</p>
                        @else
                            <p class="mapping-muted">Optional mapping</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
