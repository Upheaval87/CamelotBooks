<x-app-layout>
    <x-list-header title="{{ __('Numbering Sequence Details') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('admin.numbering-sequences.edit', $numberingSequence) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('admin.numbering-sequences.index') }}" class="tr-item">{{ __('Back to List') }}</a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Document Type')" :value="$labels[$numberingSequence->document_type] ?? $numberingSequence->document_type" />
                            <x-detail-field :label="__('Status')" noBorder>
                                @if($numberingSequence->is_active)
                                    <span class="status-pill positive">{{ __('Active') }}</span>
                                @else
                                    <span class="status-pill negative">{{ __('Inactive') }}</span>
                                @endif
                            </x-detail-field>
                            <x-detail-field :label="__('Prefix')">
                                <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $numberingSequence->prefix }}</code>
                            </x-detail-field>
                            <x-detail-field :label="__('Zero-Padding Width')" :value="$numberingSequence->padding_width . ' digits'" />
                            <x-detail-field :label="__('Current Next Number')">
                                {{ str_pad($numberingSequence->next_number, $numberingSequence->padding_width, '0', STR_PAD_LEFT) }}
                            </x-detail-field>
                            <x-detail-field :label="__('Reset Policy')">
                                <span class="status-pill neutral">{{ ucfirst($numberingSequence->reset_policy) }}</span>
                            </x-detail-field>
                            <x-detail-field :label="__('Next Document Number Preview')">
                                <code class="font-sans">{{ $nextPreview ?? 'N/A' }}</code>
                            </x-detail-field>
                            <x-detail-field :label="__('Created')" :value="$numberingSequence->created_at->format('M d, Y H:i')" />
                        </div>
                    </div>

                    <div class="card p-6">
                        <form method="POST" action="{{ route('admin.numbering-sequences.reset', $numberingSequence) }}" onsubmit="return confirm('Are you sure you want to reset this sequence to 1? This should only be done at the start of a new period.')">
                            @csrf
                            <button type="submit" class="x-button x-button-ghost">{{ __('Reset Sequence to 1') }}</button>
                        </form>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('admin.numbering-sequences.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
