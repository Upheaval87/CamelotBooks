<x-app-layout>
    <x-list-header title="{{ __('Depreciation Runs') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Run New Depreciation') }}</h3>
                <form method="POST" action="{{ route('accounting.depreciation.run') }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="period" value="{{ __('Period (YYYY-MM)') }}" />
                        <x-text-input id="period" name="period" type="month" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-primary-button type="submit">{{ __('Run Depreciation') }}</x-primary-button>
                    </div>
                </form>
            </div>

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Run Number</th>
                                <th>Period</th>
                                <th class="text-center">Assets Processed</th>
                                <th class="text-center">Assets Skipped</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-center">Status</th>
                                <th>Posted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    <td>
                                        #{{ $run->id }}
                                    </td>
                                    <td>
                                        {{ $run->period }}
                                    </td>
                                    <td class="text-center">
                                        {{ $run->assets_processed }}
                                    </td>
                                    <td class="text-center">
                                        {{ $run->assets_skipped }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($run->total_amount) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $run->status === 'completed' ? 'green' : ($run->status === 'posted' ? 'blue' : 'yellow') }}-100 text-{{ $run->status === 'completed' ? 'green' : ($run->status === 'posted' ? 'blue' : 'yellow') }}-800">
                                            {{ ucfirst($run->status) }}
                                        </span>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $run->posted_at?->format('M d, Y H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No depreciation runs found.
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
