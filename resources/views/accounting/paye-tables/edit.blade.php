<x-app-layout>
    <x-slot name="header">{{ __('Edit PAYE Tax Table') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.paye-tables.show', $table) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.paye-tables.update', $table) }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="version_name" value="{{ __('Version Name') }}" />
                            <x-text-input id="version_name" name="version_name" type="text" class="mt-1 block w-full" :value="old('version_name', $table->version_name)" required />
                            <x-input-error :messages="$errors->get('version_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="effective_from" value="{{ __('Effective From') }}" />
                            <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 block w-full" :value="old('effective_from', $table->effective_from->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <x-input-label value="{{ __('Tax Bands') }}" />
                            <button type="button" id="add-band" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Band</button>
                        </div>

                        <div id="bands-container">
                            @php $oldBands = old('bands', $table->bands->sortBy('sort_order')->values()->toArray()); @endphp
                            @foreach($oldBands as $index => $band)
                                <div class="band-row grid grid-cols-12 gap-3 mb-3 items-end">
                                    <div class="col-span-4">
                                        @if($index === 0)
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Threshold (MWK)</label>
                                        @endif
                                        <input type="number" name="bands[{{ $index }}][threshold]" value="{{ $band['threshold'] ?? '' }}" step="0.01" min="0"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="0.00" required>
                                    </div>
                                    <div class="col-span-4">
                                        @if($index === 0)
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Upper Limit (MWK)</label>
                                        @endif
                                        <input type="number" name="bands[{{ $index }}][upper_limit]" value="{{ $band['upper_limit'] ?? '' }}" step="0.01" min="0"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="No limit (last band)">
                                    </div>
                                    <div class="col-span-3">
                                        @if($index === 0)
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Rate (%)</label>
                                        @endif
                                        <input type="number" name="bands[{{ $index }}][rate]" value="{{ $band['rate'] ?? '' }}" step="0.01" min="0" max="100"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="0" required>
                                    </div>
                                    <div class="col-span-1 text-right">
                                        @if($index === 0)
                                            <label class="block text-xs text-gray-500 mb-1">&nbsp;</label>
                                        @endif
                                        <button type="button" class="remove-band text-red-500 hover:text-red-700 text-sm" title="Remove">&times;</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="mt-2 text-xs text-gray-500">The last band should have no upper limit (leave blank) to cover all income above the threshold.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.paye-tables.show', $table) }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('bands-container');
            const addBtn = document.getElementById('add-band');
            let bandIndex = container.querySelectorAll('.band-row').length;

            addBtn.addEventListener('click', function() {
                const row = document.createElement('div');
                row.className = 'band-row grid grid-cols-12 gap-3 mb-3 items-end';
                row.innerHTML = `
                    <div class="col-span-4">
                        <input type="number" name="bands[${bandIndex}][threshold]" step="0.01" min="0"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            placeholder="0.00" required>
                    </div>
                    <div class="col-span-4">
                        <input type="number" name="bands[${bandIndex}][upper_limit]" step="0.01" min="0"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            placeholder="No limit (last band)">
                    </div>
                    <div class="col-span-3">
                        <input type="number" name="bands[${bandIndex}][rate]" step="0.01" min="0" max="100"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            placeholder="0" required>
                    </div>
                    <div class="col-span-1 text-right">
                        <button type="button" class="remove-band text-red-500 hover:text-red-700 text-sm" title="Remove">&times;</button>
                    </div>
                `;
                container.appendChild(row);
                bandIndex++;
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-band')) {
                    const rows = container.querySelectorAll('.band-row');
                    if (rows.length > 1) {
                        e.target.closest('.band-row').remove();
                    }
                }
            });
        });
    </script>
</x-app-layout>
