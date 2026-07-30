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

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <p class="text-base font-semibold text-ink">{{ $build->build_number }}</p>
                                @if($build->type === 'build')
                                    <span class="status-pill neutral">{{ __('Build') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('Unbuild') }}</span>
                                @endif
                                <span class="status-pill positive">{{ __('Completed') }}</span>
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
                            @if($build->journalEntry)
                                <x-detail-field label="{{ __('Journal Entry') }}">
                                    <a href="{{ route('accounting.journal-entries.show', $build->journalEntry) }}" class="text-ink hover:text-gold">
                                        {{ $build->journalEntry->entry_number }}
                                    </a>
                                </x-detail-field>
                            @endif
                            <x-detail-field label="{{ __('Created By') }}">{{ $build->creator->name ?? '—' }}</x-detail-field>
                            @if($build->memo)
                                <x-detail-field label="{{ __('Description') }}">{{ $build->memo }}</x-detail-field>
                            @endif
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.assemblies.print', $build), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.assemblies.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
