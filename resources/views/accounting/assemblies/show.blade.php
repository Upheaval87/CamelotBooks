<x-app-layout>
    <x-slot name="header">{{ __('Build') }} {{ $build->build_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.assemblies.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </a>
            </x-record-toolbar>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <p class="text-base font-semibold text-ink">{{ $build->build_number }}</p>
                        @if($build->type === 'build')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ __('Build') }}</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">{{ __('Unbuild') }}</span>
                        @endif
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('Completed') }}</span>
                    </div>
                    <div class="text-right text-sm text-ink-soft">{{ $build->date->format('M d, Y') }}</div>
                </div>

                <div class="detail-grid">
                    <x-detail-field label="{{ __('Assembly Product') }}" strong>{{ $build->assemblyProduct->name ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('SKU') }}">{{ $build->assemblyProduct->sku ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Quantity') }}" strong>{{ format_money($build->quantity) }}</x-detail-field>
                    <x-detail-field label="{{ __('Unit Cost') }}">{{ format_money($build->unit_cost, null, 4) }}</x-detail-field>
                    <x-detail-field label="{{ __('Total Component Cost') }}" strong>@money($build->total_component_cost)</x-detail-field>
                    @if($build->billOfMaterial)
                        <x-detail-field label="{{ __('BOM') }}">{{ $build->billOfMaterial->bom_number }}</x-detail-field>
                    @endif
                    @if($build->memo)
                        <x-detail-field label="{{ __('Memo') }}">{{ $build->memo }}</x-detail-field>
                    @endif
                    @if($build->journalEntry)
                        <x-detail-field label="{{ __('Journal Entry') }}">
                            <a href="{{ route('accounting.journal-entries.show', $build->journalEntry) }}" class="text-ink hover:text-gold">
                                {{ $build->journalEntry->entry_number }}
                            </a>
                        </x-detail-field>
                    @endif
                    <x-detail-field label="{{ __('Created By') }}">{{ $build->creator->name ?? '—' }}</x-detail-field>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
