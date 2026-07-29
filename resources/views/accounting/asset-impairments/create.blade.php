<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Asset Impairment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-impairments.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="asset_id" value="{{ __('Fixed Asset') }}" />
                            <select id="asset_id" name="asset_id" class="input mt-1" required>
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
                            <x-input-label for="impairment_date" value="{{ __('Impairment Date') }}" />
                            <x-text-input id="impairment_date" name="impairment_date" type="date" class="mt-1 block w-full" :value="old('impairment_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('impairment_date')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="carrying_amount" value="{{ __('Carrying Amount') }}" />
                                <x-text-input id="carrying_amount" name="carrying_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('carrying_amount')" required />
                                <x-input-error :messages="$errors->get('carrying_amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="recoverable_amount" value="{{ __('Recoverable Amount') }}" />
                                <x-text-input id="recoverable_amount" name="recoverable_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('recoverable_amount')" required />
                                <x-input-error :messages="$errors->get('recoverable_amount')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="impairment_loss" value="{{ __('Impairment Loss') }}" />
                            <x-text-input id="impairment_loss" name="impairment_loss" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('impairment_loss')" required />
                            <x-input-error :messages="$errors->get('impairment_loss')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" value="{{ __('Reason') }}" />
                            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-impairments.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Record Impairment') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
