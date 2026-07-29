<x-app-layout>
    <x-slot name="header">{{ __('Edit Numbering Sequence') }}</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Numbering Sequence</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.numbering-sequences.show', $numberingSequence) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 transition ease-in-out duration-150">
                    Back to View
                </a>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.numbering-sequences.update', $numberingSequence) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Document Type</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $labels[$numberingSequence->document_type] ?? $numberingSequence->document_type }}</p>
                        <input type="hidden" name="document_type" value="{{ $numberingSequence->document_type }}">
                    </div>

                    <div>
                        <label for="prefix" class="block text-sm font-medium text-gray-700">Prefix</label>
                        <input type="text" name="prefix" id="prefix" value="{{ old('prefix', $numberingSequence->prefix) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        @error('prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="padding_width" class="block text-sm font-medium text-gray-700">Zero-Padding Width</label>
                        <input type="number" name="padding_width" id="padding_width" value="{{ old('padding_width', $numberingSequence->padding_width) }}" min="1" max="10" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        @error('padding_width') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="next_number" class="block text-sm font-medium text-gray-700">Next Number</label>
                        <input type="number" name="next_number" id="next_number" value="{{ old('next_number', $numberingSequence->next_number) }}" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        <p class="mt-1 text-xs text-gray-500">Warning: changing this can cause duplicate numbers if documents already exist with the old sequence.</p>
                        @error('next_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reset_policy" class="block text-sm font-medium text-gray-700">Reset Policy</label>
                        <select name="reset_policy" id="reset_policy" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="never" {{ old('reset_policy', $numberingSequence->reset_policy) === 'never' ? 'selected' : '' }}>Never Reset</option>
                            <option value="annually" {{ old('reset_policy', $numberingSequence->reset_policy) === 'annually' ? 'selected' : '' }}>Reset Annually</option>
                            <option value="monthly" {{ old('reset_policy', $numberingSequence->reset_policy) === 'monthly' ? 'selected' : '' }}>Reset Monthly</option>
                        </select>
                        @error('reset_policy') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $numberingSequence->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.numbering-sequences.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Update Sequence
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
