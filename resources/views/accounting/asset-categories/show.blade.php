<x-app-layout>
    <x-slot name="header">{{ __('Asset Category Detail') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.asset-categories.edit', $category) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.asset-categories.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Categories') }}
                </a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('General Information') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Code') }}" strong>{{ $category->code }}</x-detail-field>
                    <x-detail-field label="{{ __('Name') }}" strong>{{ $category->name }}</x-detail-field>
                    <x-detail-field label="{{ __('Description') }}">{{ $category->description ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Revaluation') }}">
                        @if($category->is_revaluation_enabled)
                            <span class="status-pill neutral">{{ __('Enabled') }}</span>
                        @else
                            <span class="status-pill neutral">{{ __('Disabled') }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field label="{{ __('Status') }}">
                        @if($category->is_active)
                            <span class="status-pill positive">{{ __('Active') }}</span>
                        @else
                            <span class="status-pill neutral">{{ __('Inactive') }}</span>
                        @endif
                    </x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Financial Depreciation') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Depreciation Method') }}">{{ str_replace('_', ' ', ucfirst($category->depreciation_method_financial)) }}</x-detail-field>
                    <x-detail-field label="{{ __('Useful Life') }}">{{ $category->useful_life_financial }} {{ __('years') }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value Type') }}">{{ ucfirst($category->residual_value_type_financial) }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value') }}">{{ format_money($category->residual_value_financial) }}</x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Tax Depreciation') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Depreciation Method') }}">{{ str_replace('_', ' ', ucfirst($category->depreciation_method_tax)) }}</x-detail-field>
                    <x-detail-field label="{{ __('Useful Life') }}">{{ $category->useful_life_tax }} {{ __('years') }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value Type') }}">{{ ucfirst($category->residual_value_type_tax) }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value') }}">{{ format_money($category->residual_value_tax) }}</x-detail-field>
                    <x-detail-field label="{{ __('Depreciation Rate') }}">{{ $category->depreciation_rate_tax ? $category->depreciation_rate_tax . '%' : '—' }}</x-detail-field>
                </div>
            </div>

            @if($category->assets->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Assets in this Category') }} ({{ $category->assets->count() }})</p>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Name</th>
                                    <th class="text-right">Acquisition Cost</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->assets as $asset)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="text-ink hover:text-gold">
                                                {{ $asset->asset_code }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="hover:text-indigo-600">
                                                {{ $asset->name }}
                                            </a>
                                        </td>
                                        <td class="numeric">
                                            {{ format_money($asset->acquisition_cost) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-100 text-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-800">
                                                {{ ucfirst($asset->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
