<x-app-layout>
    <x-list-header title="{{ __('Bills of Materials') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.assemblies.index') }}">{{ __('Builds') }}</x-button>
        <x-button variant="primary" href="{{ route('accounting.assemblies.create-bom') }}">{{ __('New BOM') }}</x-button>
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
                                <th>BOM #</th>
                                <th>Assembly Product</th>
                                <th>Name</th>
                                <th class="text-right">Components</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($boms as $bom)
                                <tr class="hover:bg-gray-50">
                                    <td>{{ $bom->bom_number }}</td>
                                    <td>{{ $bom->assemblyProduct->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $bom->name ?? '—' }}</td>
                                    <td class="numeric">{{ $bom->lines_count }}</td>
                                    <td class="text-center">
                                        @if($bom->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">
                                        No bills of materials found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $boms->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
