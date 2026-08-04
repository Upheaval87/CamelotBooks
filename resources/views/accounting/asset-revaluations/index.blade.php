<x-app-layout>
    <x-list-header title="{{ __('New Revaluation') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.asset-revaluations.create') }}">
                    {{ __('New Revaluation') }}
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
                                <th>Revaluation #</th>
                                <th>Asset</th>
                                <th>Date</th>
                                <th class="text-right">Previous Value</th>
                                <th class="text-right">Revalued Amount</th>
                                <th class="text-right">Revaluation Surplus</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revaluations as $revaluation)
                                <tr>
                                    <td>
                                        #{{ $revaluation->id }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.fixed-assets.show', $revaluation->asset) }}" class="text-ink hover:text-gold">
                                            {{ $revaluation->asset->asset_code }} - {{ $revaluation->asset->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $revaluation->revaluation_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($revaluation->previous_value ?? 0) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($revaluation->revalued_amount ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ ($revaluation->revaluation_surplus ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ format_money($revaluation->revaluation_surplus ?? 0) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $revaluation->status === 'posted' ? 'green' : 'yellow' }}-100 text-{{ $revaluation->status === 'posted' ? 'green' : 'yellow' }}-800">
                                            {{ ucfirst($revaluation->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No asset revaluations found.
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
