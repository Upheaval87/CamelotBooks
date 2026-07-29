<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asset Usage Log (Units of Production)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Log Usage') }}</h3>
                <form method="POST" action="{{ route('accounting.asset-usage.store') }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="asset_id" value="{{ __('Asset') }}" />
                        <select id="asset_id" name="asset_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Select Asset</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_code }} - {{ $asset->name }}
                                </option>
                            @endforeach
                        </select>
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
                                        {{ $log->usage_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($log->units_used) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($log->total_units_used ?? 0) }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $log->user->name ?? '—' }}
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
