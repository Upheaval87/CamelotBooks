<x-app-layout>
    <x-list-header title="{{ __('Edit Numbering Sequence') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="form-page">
            <div class="form-page-main">
                <div class="card p-6">
                    <form method="POST" action="{{ route('admin.numbering-sequences.update', $numberingSequence) }}">
                        @csrf
                        @method('PUT')

                        <x-form.section number="01" title="Sequence Details" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Document Type" />
                                <p class="mt-1 text-sm text-gray-900">{{ $labels[$numberingSequence->document_type] ?? $numberingSequence->document_type }}</p>
                                <input type="hidden" name="document_type" value="{{ $numberingSequence->document_type }}">
                            </div>

                            <div>
                                <x-input-label for="prefix" value="Prefix" />
                                <input type="text" name="prefix" id="prefix" value="{{ old('prefix', $numberingSequence->prefix) }}" class="input mt-1" required>
                                @error('prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="padding_width" value="Zero-Padding Width" />
                                <input type="number" name="padding_width" id="padding_width" value="{{ old('padding_width', $numberingSequence->padding_width) }}" min="1" max="10" class="input mt-1" required>
                                @error('padding_width') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="next_number" value="Next Number" />
                                <input type="number" name="next_number" id="next_number" value="{{ old('next_number', $numberingSequence->next_number) }}" min="1" class="input mt-1" required>
                                <p class="mt-1 text-xs text-gray-500">Warning: changing this can cause duplicate numbers if documents already exist with the old sequence.</p>
                                @error('next_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="reset_policy" value="Reset Policy" />
                                <select name="reset_policy" id="reset_policy" class="input mt-1" required>
                                    <option value="never" {{ old('reset_policy', $numberingSequence->reset_policy) === 'never' ? 'selected' : '' }}>Never Reset</option>
                                    <option value="annually" {{ old('reset_policy', $numberingSequence->reset_policy) === 'annually' ? 'selected' : '' }}>Reset Annually</option>
                                    <option value="monthly" {{ old('reset_policy', $numberingSequence->reset_policy) === 'monthly' ? 'selected' : '' }}>Reset Monthly</option>
                                </select>
                                @error('reset_policy') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $numberingSequence->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <x-input-label for="is_active" value="Active" class="ml-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <x-button variant="ghost" href="{{ route('admin.numbering-sequences.index') }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Update Sequence') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                ['label' => __('View'), 'links' => [
                    ['title' => __('Back to List'), 'route' => route('admin.numbering-sequences.index'), 'icon' => 'back'],
                ]],
            ]" />
        </div>
    </div>
</div>
</x-app-layout>
