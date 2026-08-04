<x-app-layout>
    <x-slot name="header">{{ __('New Stock Count') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                            A new stock count will include all tracked inventory products with their current expected quantities. Enter physical counts and post to generate variance adjustments.
                        </div>

                        <form method="POST" action="{{ route('accounting.stock-counts.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Stock Count Details')" />

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="date" value="{{ __('Count Date') }}" />
                                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                                </div>

                                <div>
                                    <x-input-label for="branch_id" value="{{ __('Branch (optional)') }}" />
                                    <p class="text-xs text-gray-500 mb-1">Leave blank to count across all locations.</p>
                                    <x-scoped-search-field
                                        name="branch_id"
                                        entity="branch"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                        :value="old('branch_id')"
                                        :label="old('branch_id') ? ($branches->firstWhere('id', (int) old('branch_id'))?->name ?? '') : ''"
                                        placeholder="{{ __('All Locations') }}"
                                    />
                                </div>

                                <div>
                                    <x-input-label for="notes" value="{{ __('Notes (optional)') }}" />
                                    <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes')" placeholder="Optional notes" />
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.stock-counts.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Create Count') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
