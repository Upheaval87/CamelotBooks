<x-app-layout>
    <x-list-header title="{{ __('Depreciation Schedule') }}: {{ $asset->asset_code }} - {{ $asset->name }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.fixed-assets.show', $asset) }}">{{ __('Back to Asset') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Financial Book') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-right">Opening NBV</th>
                                <th class="text-right">Depreciation Charge</th>
                                <th class="text-right">Accumulated Depreciation</th>
                                <th class="text-right">Closing NBV</th>
                                <th class="text-center">Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($financialSchedule as $row)
                                <tr>
                                    <td>{{ $row['period'] }}</td>
                                    <td class="numeric">{{ format_money($row['opening_nbv']) }}</td>
                                    <td class="numeric">{{ format_money($row['depreciation_charge']) }}</td>
                                    <td class="numeric">{{ format_money($row['accumulated_depreciation']) }}</td>
                                    <td class="numeric">{{ format_money($row['closing_nbv']) }}</td>
                                    <td class="text-center">
                                        @if($row['is_posted'])
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No financial depreciation schedule found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Tax Book') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-right">Opening NBV</th>
                                <th class="text-right">Depreciation Charge</th>
                                <th class="text-right">Accumulated Depreciation</th>
                                <th class="text-right">Closing NBV</th>
                                <th class="text-center">Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taxSchedule as $row)
                                <tr>
                                    <td>{{ $row['period'] }}</td>
                                    <td class="numeric">{{ format_money($row['opening_nbv']) }}</td>
                                    <td class="numeric">{{ format_money($row['depreciation_charge']) }}</td>
                                    <td class="numeric">{{ format_money($row['accumulated_depreciation']) }}</td>
                                    <td class="numeric">{{ format_money($row['closing_nbv']) }}</td>
                                    <td class="text-center">
                                        @if($row['is_posted'])
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No tax depreciation schedule found.
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
