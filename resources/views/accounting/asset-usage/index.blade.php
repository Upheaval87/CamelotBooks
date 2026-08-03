<x-app-layout>
    <x-slot name="header">{{ __('Asset Usage Log (Units of Production)') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Log Usage') }}</h3>
                <form method="POST" action="{{ route('accounting.asset-usage.store') }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="asset_id" value="{{ __('Asset') }}" />
                        <x-scoped-search-field
                            name="asset_id"
                            entity="asset"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'asset']) }}"
                            :value="old('asset_id')"
                            :label="old('asset_name', ($assets->firstWhere('id', (int) old('asset_id'))?->name ?? ''))"
                            placeholder="{{ __('Search assets...') }}"
                            required
                        />
                    </div>
                    <div>
                        <x-input-label for="usage_date" value="{{ __('Date') }}" />
                        <x-text-input id="usage_date" name="usage_date" type="date" class="mt-1 block w-full" :value="old('usage_date', date('Y-m-d'))" required />
                    </div>
                    <div>
                        <x-input-label for="units_used" value="{{ __('Units Used') }}" />
                        <x-text-input id="units_used" name="units_used" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('units_used')" required />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Log Usage') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Date</th>
                                <th class="text-right">Units Used</th>
                                <th class="text-right">Total Units to Date</th>
                                <th>Logged By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageLogs as $log)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $log->asset) }}" class="text-ink hover:text-gold">
                                            {{ $log->asset->asset_code }} - {{ $log->asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $log->period_start_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($log->units_used) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($log->cumulative_units ?? 0) }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $log->createdBy?->name ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">
                                        No usage records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
