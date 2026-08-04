<x-app-layout>
    <x-list-header title="{{ __('New Count') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.stock-counts.create') }}">
                    {{ __('New Count') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Count #</th>
                                <th>Date</th>
                                <th>Branch</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Variance Total</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($counts as $count)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.stock-counts.show', $count) }}" class="text-ink hover:text-gold">{{ $count->count_number }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $count->date->format('M d, Y') }}</td>
                                    <td class="text-ink-soft">{{ $count->branch->name ?? 'All Locations' }}</td>
                                    <td class="text-center">
                                        @if($count->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">In Progress</span>
                                        @endif
                                    </td>
                                    <td class="numeric">@money($count->variance_total)</td>
                                    <td class="text-center">
                                        <a href="{{ route('accounting.stock-counts.show', $count) }}" class="text-ink hover:text-gold">View</a>
                                        @if($count->status === 'in_progress')
                                            <a href="{{ route('accounting.stock-counts.edit', $count) }}" class="text-ink hover:text-gold">Count</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">No stock counts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">{{ $counts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
