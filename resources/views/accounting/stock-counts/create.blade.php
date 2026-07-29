<x-app-layout>
    <x-slot name="header">{{ __('New Stock Count') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.stock-counts.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card p-6">
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                    A new stock count will include all tracked inventory products with their current expected quantities. Enter physical counts and post to generate variance adjustments.
                </div>

                <form method="POST" action="{{ route('accounting.stock-counts.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="date" value="{{ __('Count Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                        </div>

                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch (optional)') }}" />
                            <p class="text-xs text-gray-500 mb-1">Leave blank to count across all locations.</p>
                            <select id="branch_id" name="branch_id" class="input mt-1">
                                <option value="">All Locations</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="notes" value="{{ __('Notes (optional)') }}" />
                            <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes')" placeholder="Optional notes" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <x-button variant="ghost" href="{{ route('accounting.stock-counts.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Create Count') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
