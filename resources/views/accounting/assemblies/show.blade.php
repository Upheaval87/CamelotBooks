<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Build') }} {{ $build->build_number }}</h2>
            <a href="{{ route('accounting.assemblies.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $build->build_number }}</h3>
                        @if($build->type === 'build')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Build</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Unbuild</span>
                        @endif
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                    </div>
                    <div class="text-right text-sm text-gray-500">{{ $build->date->format('M d, Y') }}</div>
                </div>

                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Assembly Product</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $build->assemblyProduct->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Stock Keeping Unit (SKU)</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $build->assemblyProduct->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Quantity</dt>
                        <dd class="text-sm text-gray-900 font-bold mt-1">{{ format_money($build->quantity) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Unit Cost</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ format_money($build->unit_cost, null, 4) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Total Component Cost</dt>
                        <dd class="text-sm text-gray-900 font-bold mt-1">@money($build->total_component_cost)</dd>
                    </div>
                    @if($build->billOfMaterial)
                        <div>
                            <dt class="text-sm text-gray-500">BOM</dt>
                            <dd class="text-sm text-gray-900 font-medium mt-1">{{ $build->billOfMaterial->bom_number }}</dd>
                        </div>
                    @endif
                    @if($build->memo)
                        <div class="col-span-2">
                            <dt class="text-sm text-gray-500">Memo</dt>
                            <dd class="text-sm text-gray-900 font-medium mt-1">{{ $build->memo }}</dd>
                        </div>
                    @endif
                    @if($build->journalEntry)
                        <div>
                            <dt class="text-sm text-gray-500">Journal Entry</dt>
                            <dd class="text-sm text-indigo-600 font-medium mt-1">
                                <a href="{{ route('accounting.journal-entries.show', $build->journalEntry) }}" class="hover:text-indigo-900">
                                    {{ $build->journalEntry->entry_number }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm text-gray-500">Created By</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $build->creator->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
