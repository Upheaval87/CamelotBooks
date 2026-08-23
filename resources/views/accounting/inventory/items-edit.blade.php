<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('accounting.inventory.items') }}">{{ __('Items') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('accounting.inventory.items.show', $product) }}">{{ $product->name }}</a>
            <span class="sep">/</span>
            <span>{{ __('Edit') }}</span>
        </div>

        <div class="inv-sticky-head">
            <div style="display:flex;align-items:center;gap:12px">
                <h1>{{ __('Edit Item') }} — {{ $product->name }}</h1>
                @if($product->sku)
                <span class="inv-mono" style="font-size:12px;font-weight:600;color:var(--muted);background:rgba(17,69,75,.06);padding:3px 10px;border-radius:8px;border:1px solid rgba(17,69,75,.12)">{{ $product->sku }}</span>
                @endif
                @if($hasTransactions)
                <span style="font-size:11px;font-weight:700;color:var(--amber-2,#b45309);background:rgba(180,83,9,.08);padding:3px 10px;border-radius:8px;border:1px solid rgba(180,83,9,.18)">{{ __('Has transactions') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <a href="{{ route('accounting.inventory.items.show', $product) }}" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Cancel') }}</a>
                <button type="submit" form="inv-item-form" name="action" value="save" class="inv-btn inv-btn-cta inv-btn-sm">{{ __('Save Changes') }}</button>
            </div>
        </div>

        @include('accounting.inventory._form', [
            'isEdit' => true,
            'hasStockMovements' => $hasStockMovements,
            'hasTransactions' => $hasTransactions,
        ])

        <div class="inv-actionbar">
            <div style="margin-right:auto;font-size:12px;color:var(--muted);font-weight:700">{{ __('Fields marked * are required') }}</div>
            <a href="{{ route('accounting.inventory.items.show', $product) }}" class="inv-btn inv-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" form="inv-item-form" name="action" value="save" class="inv-btn inv-btn-cta">{{ __('Save Changes') }}</button>
        </div>
    </div>
</x-app-layout>
