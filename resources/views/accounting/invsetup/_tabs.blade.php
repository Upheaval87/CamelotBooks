{{-- Inventory Setup shared tab bar — pass $activeTab (categories|assemblies|transfers|counts|uom|valuation|lowstock) --}}
<div class="inv-tabs">
    <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab {{ ($activeTab ?? '') === 'categories' ? 'inv-tab-on' : '' }}">{{ __('Item Categories') }}</a>
    <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab {{ ($activeTab ?? '') === 'assemblies' ? 'inv-tab-on' : '' }}">{{ __('Assemblies') }}</a>
    <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab {{ ($activeTab ?? '') === 'transfers' ? 'inv-tab-on' : '' }}">{{ __('Transfers & Adjustments') }}</a>
    <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab {{ ($activeTab ?? '') === 'counts' ? 'inv-tab-on' : '' }}">{{ __('Stock Count') }}</a>
    <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab {{ ($activeTab ?? '') === 'uom' ? 'inv-tab-on' : '' }}">{{ __('UOM & Landed Costs') }}</a>
    <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab {{ ($activeTab ?? '') === 'valuation' ? 'inv-tab-on' : '' }}">{{ __('Valuation') }}</a>
    <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab {{ ($activeTab ?? '') === 'lowstock' ? 'inv-tab-on' : '' }}">{{ __('Low Stock') }}</a>
</div>
