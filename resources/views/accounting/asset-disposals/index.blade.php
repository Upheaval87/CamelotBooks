<x-app-layout>
    <x-list-header title="{{ __('New Disposal') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.asset-disposals.create') }}">
                    {{ __('New Disposal') }}
                </x-button>
            </div>
            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Disposal #</th>
                                <th>Asset</th>
                                <th>Disposal Date</th>
                                <th>Method</th>
                                <th class="text-right">Proceeds</th>
                                <th class="text-right">Gain/Loss</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($disposals as $disposal)
                                <tr>
                                    <td>
                                        #{{ $disposal->id }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $disposal->asset) }}" class="text-ink hover:text-gold">
                                            {{ $disposal->asset->asset_code }} - {{ $disposal->asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $disposal->disposal_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ str_replace('_', ' ', ucfirst($disposal->disposal_method ?? '—')) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($disposal->proceeds ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ ($disposal->gain_loss ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ format_money($disposal->gain_loss ?? 0) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $disposal->status === 'completed' ? 'green' : 'yellow' }}-100 text-{{ $disposal->status === 'completed' ? 'green' : 'yellow' }}-800">
                                            {{ ucfirst($disposal->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No asset disposals found.
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
