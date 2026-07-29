<x-app-layout>
    <x-slot name="header">{{ __('Import Bank Statement') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}">{{ __('Back to Reconciliation') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Upload CSV Statement') }}</h3>
                <form method="POST" action="{{ route('accounting.bank-reconciliation.import', $bankAccount->id) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}" />

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="csv_file" value="{{ __('CSV File') }}" />
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                            <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                        </div>

                        <div class="border-t pt-6">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">{{ __('Column Mapping') }}</h4>
                            <p class="text-xs text-gray-500 mb-4">Map the CSV columns to the import fields. Leave unmapped for columns you don't need.</p>

                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="date_column" value="{{ __('Date Column') }}" />
                                    <x-text-input id="date_column" name="date_column" type="text" class="mt-1 block w-full" :value="old('date_column', 'Date')" placeholder="Column header name" />
                                    <x-input-error :messages="$errors->get('date_column')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="description_column" value="{{ __('Description Column') }}" />
                                    <x-text-input id="description_column" name="description_column" type="text" class="mt-1 block w-full" :value="old('description_column', 'Description')" placeholder="Column header name" />
                                    <x-input-error :messages="$errors->get('description_column')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="debit_column" value="{{ __('Debit Column') }}" />
                                    <x-text-input id="debit_column" name="debit_column" type="text" class="mt-1 block w-full" :value="old('debit_column', 'Debit')" placeholder="Column header name" />
                                    <x-input-error :messages="$errors->get('debit_column')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="credit_column" value="{{ __('Credit Column') }}" />
                                    <x-text-input id="credit_column" name="credit_column" type="text" class="mt-1 block w-full" :value="old('credit_column', 'Credit')" placeholder="Column header name" />
                                    <x-input-error :messages="$errors->get('credit_column')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="reference_column" value="{{ __('Reference Column') }}" />
                                    <x-text-input id="reference_column" name="reference_column" type="text" class="mt-1 block w-full" :value="old('reference_column')" placeholder="Optional column header" />
                                    <x-input-error :messages="$errors->get('reference_column')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="balance_column" value="{{ __('Balance Column') }}" />
                                    <x-text-input id="balance_column" name="balance_column" type="text" class="mt-1 block w-full" :value="old('balance_column')" placeholder="Optional column header" />
                                    <x-input-error :messages="$errors->get('balance_column')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="date_format" value="{{ __('Date Format') }}" />
                            <select id="date_format" name="date_format" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="Y-m-d" {{ old('date_format') === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                <option value="m/d/Y" {{ old('date_format', 'm/d/Y') === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                <option value="d/m/Y" {{ old('date_format') === 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                <option value="M d, Y" {{ old('date_format') === 'M d, Y' ? 'selected' : '' }}>Mon DD, YYYY</option>
                                <option value="d-M-Y" {{ old('date_format') === 'd-M-Y' ? 'selected' : '' }}>DD-Mon-YYYY</option>
                            </select>
                            <x-input-error :messages="$errors->get('date_format')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Import') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
