<x-app-layout>
    <x-slot name="header">{{ __('Add Numbering Sequence') }}</x-slot>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="form-page">
            <div class="form-page-main">
                <div class="card p-6">
                    <form method="POST" action="{{ route('admin.numbering-sequences.store') }}">
                        @csrf

                        <x-form.section number="01" title="Sequence Details" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="document_type" value="Document Type" />
                                <select name="document_type" id="document_type" class="input mt-1" required>
                                    <option value="">Select document type...</option>
                                    @foreach($availableTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('document_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('document_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="prefix" value="Prefix" />
                                <input type="text" name="prefix" id="prefix" value="{{ old('prefix') }}" class="input mt-1" required placeholder="e.g. INV-">
                                @error('prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="padding_width" value="Zero-Padding Width" />
                                <input type="number" name="padding_width" id="padding_width" value="{{ old('padding_width', 4) }}" min="1" max="10" class="input mt-1" required>
                                <p class="mt-1 text-xs text-gray-500">Number of digits. E.g., 4 produces 0001, 0002, etc.</p>
                                @error('padding_width') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="reset_policy" value="Reset Policy" />
                                <select name="reset_policy" id="reset_policy" class="input mt-1" required>
                                    <option value="never" {{ old('reset_policy') === 'never' ? 'selected' : '' }}>Never Reset</option>
                                    <option value="annually" {{ old('reset_policy') === 'annually' ? 'selected' : '' }}>Reset Annually</option>
                                    <option value="monthly" {{ old('reset_policy') === 'monthly' ? 'selected' : '' }}>Reset Monthly</option>
                                </select>
                                @error('reset_policy') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <x-input-label for="is_active" value="Active" class="ml-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-3">
                            <x-button variant="ghost" href="{{ route('admin.numbering-sequences.index') }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Create Sequence') }}</x-primary-button>
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
