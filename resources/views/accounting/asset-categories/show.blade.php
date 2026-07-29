<x-app-layout>
    <x-slot name="header">{{ __('Asset Category Detail') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" href="{{ route('accounting.asset-categories.edit', $category) }}">{{ __('Edit') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.asset-categories.index') }}">{{ __('Back to Categories') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('General Information') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->name }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Revaluation') }}</dt>
                        <dd class="mt-1">
                            @if($category->is_revaluation_enabled)
                                <span class="status-pill neutral">Enabled</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">Disabled</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($category->is_active)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Financial Depreciation') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Depreciation Method') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($category->depreciation_method_financial)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Useful Life') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->useful_life_financial }} years</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($category->residual_value_type_financial) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ format_money($category->residual_value_financial) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Tax Depreciation') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Depreciation Method') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($category->depreciation_method_tax)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Useful Life') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->useful_life_tax }} years</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($category->residual_value_type_tax) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ format_money($category->residual_value_tax) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Depreciation Rate') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $category->depreciation_rate_tax ? $category->depreciation_rate_tax . '%' : '—' }}</dd>
                    </div>
                </div>
            </div>

            @if($category->assets->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Assets in this Category') }} ({{ $category->assets->count() }})</h3>
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
