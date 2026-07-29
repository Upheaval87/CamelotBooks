<x-app-layout>
    <x-slot name="header">{{ __('Numbering Sequence Details') }}</x-slot>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Numbering Sequence Details</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.numbering-sequences.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 transition ease-in-out duration-150">
                    Back to List
                </a>
                <a href="{{ route('admin.numbering-sequences.edit', $numberingSequence) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Document Type</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $labels[$numberingSequence->document_type] ?? $numberingSequence->document_type }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Status</label>
                    <p class="mt-1">
                        @if($numberingSequence->is_active)
                            <span class="status-pill positive">Active</span>
                        @else
                            <span class="status-pill negative">Inactive</span>
                        @endif
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Prefix</label>
                    <p class="mt-1 text-sm text-gray-900"><code class="bg-gray-100 px-2 py-0.5 rounded">{{ $numberingSequence->prefix }}</code></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Zero-Padding Width</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $numberingSequence->padding_width }} digits</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Current Next Number</label>
                    <p class="mt-1 text-sm text-gray-900">{{ str_pad($numberingSequence->next_number, $numberingSequence->padding_width, '0', STR_PAD_LEFT) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Reset Policy</label>
                    <p class="mt-1">
                        <span class="status-pill neutral">
                            {{ ucfirst($numberingSequence->reset_policy) }}
                        </span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Next Document Number Preview</label>
                    <p class="mt-1 text-sm text-gray-900 font-mono">{{ $nextPreview ?? 'N/A' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Created</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $numberingSequence->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <form method="POST" action="{{ route('admin.numbering-sequences.reset', $numberingSequence) }}" onsubmit="return confirm('Are you sure you want to reset this sequence to 1? This should only be done at the start of a new period.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 transition ease-in-out duration-150">
                        Reset Sequence to 1
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
