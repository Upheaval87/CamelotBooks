<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $depLabel = 'Less: ' . $cs . format_number($depTotal) . ' depreciation';
    @endphp

    <div class="fa-wrap py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ __('Fixed Assets Centre') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Asset register, depreciation and lifecycle management.') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('accounting.fixed-assets.register') }}" class="fa-btn fa-btn--ghost">{{ __('Asset Register') }}</a>
                    <a href="{{ route('accounting.fixed-assets.create') }}" class="fa-btn fa-btn--cta">＋ {{ __('Acquire Asset') }}</a>
                </div>
            </div>

            <div class="fa-kpi-grid mb-6">
                <div class="fa-card">
                    <span class="fa-chip fa-chip--teal">{{ __('Total Assets') }}</span>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ $totalAssets }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $activeAssets }} {{ __('active') }} · {{ $draftAssets }} {{ __('draft') }}</div>
                </div>
                <div class="fa-card">
                    <span class="fa-chip fa-chip--mint">{{ __('Total Cost') }}</span>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ $cs }}{{ format_number($costTotal) }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ __('Acquisition value') }}</div>
                </div>
                <div class="fa-card">
                    <span class="fa-chip fa-chip--steel">{{ __('Net Book Value') }}</span>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ $cs }}{{ format_number($nbvTotal) }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ __($depLabel) }}</div>
                </div>
                <div class="fa-card">
                    <span class="fa-chip fa-chip--amber">{{ __('Last Depreciation Run') }}</span>
                    <div class="mt-2 text-2xl font-bold text-gray-900">{{ $lastDepRun ? \Carbon\Carbon::parse($lastDepRun)->format('d M Y') : __('Never') }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $dueForDep }} {{ __('assets due') }}</div>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">

                    <div class="fa-card mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('Recent Assets') }}</h2>
                        @forelse($recentAssets as $asset)
                            <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 rounded-lg px-3 -mx-3 transition-colors">
                                <div>
                                    <span class="font-mono text-sm font-medium text-gray-900">{{ $asset->asset_code }}</span>
                                    <span class="ml-2 text-sm text-gray-600">{{ $asset->name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-900">{{ $cs }}{{ format_number($asset->net_book_value) }}</span>
                                    @php
                                        $statusColor = match($asset->status) {
                                            'active' => 'fa-chip--mint',
                                            'draft' => 'fa-chip--steel',
                                            default => 'fa-chip--steel',
                                        };
                                    @endphp
                                    <span class="fa-chip {{ $statusColor }} ml-2">{{ ucfirst($asset->status) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="text-sm text-gray-500 py-6 text-center">{{ __('No assets registered yet.') }}</div>
                        @endforelse
                    </div>

                    <div class="fa-card">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('Assets by Category') }}</h2>
                        @forelse($categories as $cat)
                            @if(($categoryCounts[$cat->id] ?? 0) > 0)
                                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                    <span class="text-sm text-gray-700">{{ $cat->name }}</span>
                                    <div class="text-right">
                                        <span class="text-sm font-semibold text-gray-900">{{ $categoryCounts[$cat->id] }} {{ __('assets') }}</span>
                                        <span class="ml-3 text-xs text-gray-500">{{ $cs }}{{ format_number($categoryValues[$cat->id]) }}</span>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="text-sm text-gray-500 py-6 text-center">{{ __('No categories configured.') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('Quick Actions') }}</h3>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('accounting.fixed-assets.create') }}" class="fa-btn fa-btn--ghost fa-btn--sm">{{ __('Acquire New Asset') }}</a>
                            <a href="{{ route('accounting.fixed-assets.register') }}" class="fa-btn fa-btn--ghost fa-btn--sm">{{ __('View Register') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
