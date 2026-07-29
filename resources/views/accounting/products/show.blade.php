<x-app-layout>
    <x-slot name="header">{{ __('Product Detail') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.products.edit', $product) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.products.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Products') }}
                </a>
            </x-record-toolbar>

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Name') }}" strong>{{ $product->name }}</x-detail-field>
                    <x-detail-field label="{{ __('SKU') }}" strong>{{ $product->sku ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Type') }}">{{ str_replace('_', ' ', ucfirst($product->type)) }}</x-detail-field>

                    <x-detail-field label="{{ __('Status') }}">
                        @if($product->is_active)
                            <span class="status-pill positive">{{ __('Active') }}</span>
                        @else
                            <span class="status-pill neutral">{{ __('Inactive') }}</span>
                        @endif
                    </x-detail-field>
                    @if($product->description)
                        <x-detail-field label="{{ __('Description') }}">{{ $product->description }}</x-detail-field>
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Pricing') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Sales Price') }}" strong>{{ format_money($product->sales_price ?? 0) }}</x-detail-field>
                    <x-detail-field label="{{ __('Purchase Price') }}" strong>{{ format_money($product->purchase_price ?? 0) }}</x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Accounts & Tax') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Income Account') }}">{{ $product->incomeAccount?->name ? "{$product->incomeAccount->code} - {$product->incomeAccount->name}" : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Expense Account') }}">{{ $product->expenseAccount?->name ? "{$product->expenseAccount->code} - {$product->expenseAccount->name}" : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Tax Rate') }}">{{ format_money($product->tax_rate ?? 0) }}%</x-detail-field>
                    <x-detail-field label="{{ __('Taxable') }}">{{ $product->is_taxable ? __('Yes') : __('No') }}</x-detail-field>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
