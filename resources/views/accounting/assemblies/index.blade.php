<x-app-layout>
    <x-list-header title="{{ __('Assembly Builds') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.assemblies.history') }}">{{ __('History') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.assemblies.boms') }}">{{ __('BOMs') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.assemblies.unbuild-form') }}">{{ __('Unbuild') }}</x-button>
        <x-button variant="primary" href="{{ route('accounting.assemblies.create') }}">{{ __('New Build') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Build #</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th class="text-center">Type</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Cost</th>
                                <th class="text-right">Total Cost</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($builds as $build)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.assemblies.show', $build) }}" class="text-ink hover:text-gold">
                                            {{ $build->build_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $build->date->format('M d, Y') }}</td>
                                    <td>{{ $build->assemblyProduct->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($build->type === 'build')
                                            <span class="status-pill neutral">Build</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Unbuild</span>
                                        @endif
                                    </td>
                                    <td class="numeric">{{ format_money($build->quantity) }}</td>
                                    <td class="numeric">{{ format_money($build->unit_cost, null, 4) }}</td>
                                    <td class="numeric">@money($build->total_component_cost)</td>
                                    <td class="text-center">
                                        <span class="status-pill positive">Completed</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-ink-soft">
                                        No assembly builds found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $builds->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
