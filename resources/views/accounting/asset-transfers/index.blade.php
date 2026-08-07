<x-app-layout>
    <x-list-header title="{{ __('New Transfer') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.asset-transfers.create') }}">
                    {{ __('New Transfer') }}
                </x-button>
            </div>
            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Transfer #</th>
                                <th>Asset</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Transfer Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td>
                                        #{{ $transfer->id }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $transfer->asset) }}" class="text-ink hover:text-gold">
                                            {{ $transfer->asset->asset_code }} - {{ $transfer->asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $transfer->from_branch ?? $transfer->from_cost_center ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $transfer->to_branch ?? $transfer->to_cost_center ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $transfer->transfer_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $transfer->status === 'completed' ? 'green' : 'yellow' }}-100 text-{{ $transfer->status === 'completed' ? 'green' : 'yellow' }}-800">
                                            {{ ucfirst($transfer->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No asset transfers found.
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
