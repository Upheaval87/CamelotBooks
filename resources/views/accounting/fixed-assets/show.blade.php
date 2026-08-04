<x-app-layout>
    <x-list-header title="{{ __('Fixed Asset Detail') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                @if($asset->status === 'draft')
                    <div class="tr-group">
                        <span class="tr-group-label">{{ __('Actions') }}</span>
                        <a href="{{ route('accounting.fixed-assets.edit', $asset) }}" class="tr-save">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            {{ __('Edit') }}
                        </a>
                        <form method="POST" action="{{ route('accounting.fixed-assets.activate', $asset) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Activate') }}</button>
                        </form>
                    </div>
                    <div class="tr-divider"></div>
                @endif
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reports') }}</span>
                    <a href="{{ route('accounting.fixed-assets.schedule', $asset) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ __('Depreciation Schedule') }}
                    </a>
                </div>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.fixed-assets.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Assets') }}
                </a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card p-6">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-base font-semibold text-ink">{{ __('General Information') }}</p>
                    <span class="px-3 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-100 text-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-800">
                        {{ ucfirst($asset->status) }}
                    </span>
                </div>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Asset Code') }}" strong>{{ $asset->asset_code }}</x-detail-field>
                    <x-detail-field label="{{ __('Name') }}" strong>{{ $asset->name }}</x-detail-field>
                    <x-detail-field label="{{ __('Category') }}">{{ $asset->category->name ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Serial Number') }}">{{ $asset->serial_number ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Acquisition Date') }}">{{ $asset->acquisition_date?->format('M d, Y') ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('In-Service Date') }}">{{ $asset->in_service_date?->format('M d, Y') ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Acquisition Cost') }}" strong>{{ format_money($asset->acquisition_cost) }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value') }}">{{ format_money($asset->residual_value) }}</x-detail-field>
                    @if($asset->description)
                        <x-detail-field label="{{ __('Description') }}">{{ $asset->description }}</x-detail-field>
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Financial Book') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Depreciation Method') }}">{{ str_replace('_', ' ', ucfirst($asset->depreciation_method_financial ?? '—')) }}</x-detail-field>
                    <x-detail-field label="{{ __('Useful Life') }}">{{ $asset->useful_life ? $asset->useful_life . ' years' : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Net Book Value') }}" strong>{{ format_money($asset->net_book_value ?? $asset->acquisition_cost) }}</x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Tax Book') }}</p>
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Depreciation Method') }}">{{ str_replace('_', ' ', ucfirst($asset->depreciation_method_tax ?? '—')) }}</x-detail-field>
                    <x-detail-field label="{{ __('Useful Life (Tax)') }}">{{ $asset->useful_life_tax ? $asset->useful_life_tax . ' years' : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Residual Value (Tax)') }}">{{ format_money($asset->residual_value_tax ?? 0) }}</x-detail-field>
                </div>
            </div>

            @if($asset->status === 'active')
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Actions') }}</p>
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.fixed-assets.index'), 'icon' => 'back', 'title' => __('Back to Assets')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
