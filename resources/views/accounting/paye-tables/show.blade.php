<x-app-layout>
    <x-slot name="header">{{ $table->version_name }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.paye-tables.index') }}">{{ __('Back') }}</x-button>
        <x-button variant="primary" href="{{ route('accounting.paye-tables.edit', $table) }}">{{ __('Edit') }}</x-button>
        @if(!$table->is_current)
            <form method="POST" action="{{ route('accounting.paye-tables.activate', $table) }}" onsubmit="return confirm('Are you sure you want to activate this PAYE tax table?');">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Activate') }}</x-button>
            </form>
            <form method="POST" action="{{ route('accounting.paye-tables.destroy', $table) }}" onsubmit="return confirm('Are you sure you want to delete this PAYE tax table? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <x-button variant="ghost" type="submit">{{ __('Delete') }}</x-button>
            </form>
        @endif
    </div>

    <div class="pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase">Version</div>
                        <div class="mt-1 text-sm text-gray-900">{{ $table->version_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase">Effective From</div>
                        <div class="mt-1 text-sm text-gray-900">{{ $table->effective_from->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase">Effective To</div>
                        <div class="mt-1 text-sm text-gray-900">{{ $table->effective_to ? $table->effective_to->format('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase">Status</div>
                        <div class="mt-1">
                            @if($table->is_current)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Tax Bands</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Threshold (MWK)</th>
                                <th>Upper Limit (MWK)</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table->bands->sortBy('sort_order') as $band)
                                <tr>
                                    <td class="text-ink-soft">{{ $band->sort_order + 1 }}</td>
                                    <td>{{ format_money((float) $band->threshold) }}</td>
                                    <td>{{ $band->upper_limit ? format_money((float) $band->upper_limit) : 'No limit' }}</td>
                                    <td>{{ format_money((float) $band->rate) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">No bands defined.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
