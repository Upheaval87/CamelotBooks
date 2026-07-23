<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Exchange Rates') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('Add Exchange Rate') }}</h3>
                <p class="text-sm text-gray-500 mb-4">Base currency: <strong>{{ $baseCurrency }}</strong></p>
                <form method="POST" action="{{ route('accounting.exchange-rates.store') }}" class="flex items-end gap-4">
                    @csrf
                    <div>
                        <x-input-label for="currency_from" value="{{ __('From') }}" />
                        <x-text-input id="currency_from" name="currency_from" type="text" class="mt-1 block w-24" :value="old('currency_from')" placeholder="EUR" maxlength="3" required />
                        <x-input-error :messages="$errors->get('currency_from')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="currency_to" value="{{ __('To') }}" />
                        <x-text-input id="currency_to" name="currency_to" type="text" class="mt-1 block w-24" :value="old('currency_to', $baseCurrency)" maxlength="3" required />
                        <x-input-error :messages="$errors->get('currency_to')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="rate" value="{{ __('Rate') }}" />
                        <x-text-input id="rate" name="rate" type="number" step="0.00000001" class="mt-1 block w-36" :value="old('rate')" required />
                        <x-input-error :messages="$errors->get('rate')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="effective_date" value="{{ __('Effective Date') }}" />
                        <x-text-input id="effective_date" name="effective_date" type="date" class="mt-1 block" :value="old('effective_date', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('effective_date')" class="mt-2" />
                    </div>
                    <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Bulk Import (CSV)') }}</h3>
                <form method="POST" action="{{ route('accounting.exchange-rates.bulk') }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-gray-500">Format: <code>FROM,TO,RATE,DATE</code> (one per line). Example: <code>EUR,USD,1.0850,2026-07-01</code></p>
                    <x-input-label for="csv_data" value="{{ __('CSV Data') }}" />
                    <textarea id="csv_data" name="csv_data" rows="5" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm">{{ old('csv_data') }}</textarea>
                    <x-primary-button type="submit">{{ __('Import') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Saved Rates') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rates as $rate)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $rate->currency_from }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $rate->currency_to }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right font-mono">{{ number_format($rate->rate, 8) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $rate->effective_date->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <form method="POST" action="{{ route('accounting.exchange-rates.destroy', $rate) }}" class="inline" onsubmit="return confirm('Delete this rate?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No exchange rates configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
