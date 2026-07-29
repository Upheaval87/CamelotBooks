<x-app-layout>
    <x-slot name="header">{{ __('New Impairment') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.asset-impairments.create') }}">
                    {{ __('New Impairment') }}
                </x-button>
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
                                <th>Impairment #</th>
                                <th>Asset</th>
                                <th>Date</th>
                                <th class="text-right">Carrying Amount</th>
                                <th class="text-right">Recoverable Amount</th>
                                <th class="text-right">Impairment Loss</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($impairments as $impairment)
                                <tr>
                                    <td>
                                        #{{ $impairment->id }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $impairment->asset) }}" class="text-ink hover:text-gold">
                                            {{ $impairment->asset->asset_code }} - {{ $impairment->asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $impairment->impairment_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($impairment->carrying_amount ?? 0) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($impairment->recoverable_amount ?? 0) }}
                                    </td>
                                    <td class="text-red-600 text-right font-semibold">
                                        {{ format_money($impairment->impairment_loss ?? 0) }}
                                    </td>
                                    <td class="text-center">
                                        @if($impairment->is_reversed)
                                            <span class="status-pill neutral">Reversed</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $impairment->status === 'posted' ? 'green' : 'yellow' }}-100 text-{{ $impairment->status === 'posted' ? 'green' : 'yellow' }}-800">
                                                {{ ucfirst($impairment->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No asset impairments found.
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
