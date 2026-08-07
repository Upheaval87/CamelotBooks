<x-app-layout>
    <x-list-header title="{{ __('Add Asset') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.fixed-assets.create') }}">
                    {{ __('Add Asset') }}
                </x-button>
            </div>
            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Asset Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th class="text-right">Acquisition Cost</th>
                                <th>Acquisition Date</th>
                                <th>In-Service Date</th>
                                <th class="text-right">NBV</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                                <tr class="{{ $asset->status === 'draft' ? 'bg-gray-50 text-gray-400' : '' }}">
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="text-ink hover:text-gold">
                                            {{ $asset->asset_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="hover:text-gold-700">
                                            {{ $asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $asset->category->name ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($asset->acquisition_cost) }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $asset->acquisition_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $asset->in_service_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($asset->net_book_value ?? $asset->acquisition_cost) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-100 text-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'disposed' ? 'red' : 'gray') }}-800">
                                            {{ ucfirst($asset->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.fixed-assets.show', $asset) }}" class="text-ink hover:text-gold">View</a>
                                        @if($asset->status === 'draft')
                                            <a href="{{ route('accounting.fixed-assets.edit', $asset) }}" class="text-ink hover:text-gold">Edit</a>
                                            <form method="POST" action="{{ route('accounting.fixed-assets.activate', $asset) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-ink-soft">
                                        No fixed assets found.
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
