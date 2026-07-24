<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fixed Asset Detail') }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($asset->status === 'draft')
                    <a href="{{ route('accounting.fixed-assets.edit', $asset) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Edit') }}
                    </a>
                    <form method="POST" action="{{ route('accounting.fixed-assets.activate', $asset) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Activate') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('accounting.fixed-assets.schedule', $asset) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Depreciation Schedule') }}
                </a>
                <a href="{{ route('accounting.fixed-assets.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Assets') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('General Information') }}</h3>
                    <span class="px-3 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-100 text-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-800">
                        {{ ucfirst($asset->status) }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Asset Code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->asset_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Category') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->category->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Serial Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->serial_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Acquisition Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->acquisition_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('In-Service Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->in_service_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Acquisition Cost') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($asset->acquisition_cost, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($asset->residual_value, 2) }}</dd>
                    </div>
                    @if($asset->description)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $asset->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Financial Book') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Depreciation Method') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($asset->depreciation_method_financial ?? '—')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Useful Life') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->useful_life ? $asset->useful_life . ' years' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Net Book Value') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ number_format($asset->net_book_value ?? $asset->acquisition_cost, 2) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Tax Book') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Depreciation Method') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($asset->depreciation_method_tax ?? '—')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Useful Life (Tax)') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $asset->useful_life_tax ? $asset->useful_life_tax . ' years' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Residual Value (Tax)') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($asset->residual_value_tax ?? 0, 2) }}</dd>
                    </div>
                </div>
            </div>

            @if($asset->status === 'active')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Actions') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('accounting.asset-disposals.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Dispose Asset') }}
                        </a>
                        <a href="{{ route('accounting.asset-transfers.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Transfer Asset') }}
                        </a>
                        <a href="{{ route('accounting.asset-impairments.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Record Impairment') }}
                        </a>
                        @if($asset->is_revaluation_enabled)
                            <a href="{{ route('accounting.asset-revaluations.create', ['asset_id' => $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Revalue Asset') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
