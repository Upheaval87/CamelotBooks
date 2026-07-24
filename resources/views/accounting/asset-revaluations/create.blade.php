<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Asset Revaluation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.asset-revaluations.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="asset_id" value="{{ __('Fixed Asset') }}" />
                            <select id="asset_id" name="asset_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id', request('asset_id')) == $asset->id ? 'selected' : '' }}>
                                        {{ $asset->asset_code }} - {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asset_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="revaluation_date" value="{{ __('Revaluation Date') }}" />
                            <x-text-input id="revaluation_date" name="revaluation_date" type="date" class="mt-1 block w-full" :value="old('revaluation_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('revaluation_date')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="previous_value" value="{{ __('Previous Carrying Value') }}" />
                                <x-text-input id="previous_value" name="previous_value" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('previous_value')" required />
                                <x-input-error :messages="$errors->get('previous_value')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="revalued_amount" value="{{ __('Revalued Amount') }}" />
                                <x-text-input id="revalued_amount" name="revalued_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('revalued_amount')" required />
                                <x-input-error :messages="$errors->get('revalued_amount')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="revaluation_surplus" value="{{ __('Revaluation Surplus') }}" />
                            <x-text-input id="revaluation_surplus" name="revaluation_surplus" type="number" step="0.01" class="mt-1 block w-full" :value="old('revaluation_surplus')" />
                            <x-input-error :messages="$errors->get('revaluation_surplus')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" value="{{ __('Reason') }}" />
                            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <a href="{{ route('accounting.asset-revaluations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Create Revaluation') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
