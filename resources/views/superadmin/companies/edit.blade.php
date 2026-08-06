<x-app-layout>
    <x-slot name="header">{{ __('Edit Company') }} - {{ $company->name }}</x-slot>

    @include('superadmin._nav', ['active' => 'companies'])

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.companies.update', $company) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="name">{{ __('Company Name') }}</x-input-label>
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required autofocus
                                :value="old('name', $company->name)" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="legal_name">{{ __('Legal Name') }}</x-input-label>
                            <x-text-input id="legal_name" name="legal_name" class="mt-1 block w-full" :value="old('legal_name', $company->legal_name)" />
                        </div>

                        <div>
                            <x-input-label for="company_code">{{ __('Company Code') }}</x-input-label>
                            <x-text-input id="company_code" name="company_code" class="mt-1 block w-full" :value="old('company_code', $company->company_code)" />
                            <x-input-error :messages="$errors->get('company_code')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="tax_id">{{ __('Tax ID') }}</x-input-label>
                            <x-text-input id="tax_id" name="tax_id" class="mt-1 block w-full" :value="old('tax_id', $company->tax_id)" />
                        </div>

                        <div>
                            <x-input-label for="email">{{ __('Email') }}</x-input-label>
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $company->email)" />
                        </div>

                        <div>
                            <x-input-label for="phone">{{ __('Phone') }}</x-input-label>
                            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $company->phone)" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="address">{{ __('Address') }}</x-input-label>
                            <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $company->address)" />
                        </div>

                        <div>
                            <x-input-label for="city">{{ __('City') }}</x-input-label>
                            <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city', $company->city)" />
                        </div>

                        <div>
                            <x-input-label for="state">{{ __('State / Province') }}</x-input-label>
                            <x-text-input id="state" name="state" class="mt-1 block w-full" :value="old('state', $company->state)" />
                        </div>

                        <div>
                            <x-input-label for="country">{{ __('Country') }}</x-input-label>
                            <x-text-input id="country" name="country" class="mt-1 block w-full" :value="old('country', $company->country)" />
                        </div>

                        <div>
                            <x-input-label for="postal_code">{{ __('Postal Code') }}</x-input-label>
                            <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="old('postal_code', $company->postal_code)" />
                        </div>

                        <div>
                            <x-input-label for="base_currency">{{ __('Base Currency') }}</x-input-label>
                            <select id="base_currency" name="base_currency" class="input mt-1 block w-full" required>
                                @forelse($currencies as $currency)
                                    <option value="{{ $currency->code }}" @selected(old('base_currency', $company->base_currency) === $currency->code)>{{ $currency->label() }}</option>
                                @empty
                                    <option value="{{ $company->base_currency }}" selected>{{ $company->base_currency }}</option>
                                @endforelse
                            </select>
                            <x-input-error :messages="$errors->get('base_currency')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="fiscal_year_start_month">{{ __('Fiscal Year Starts In') }}</x-input-label>
                            <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="input mt-1 block w-full" required>
                                @foreach([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $month => $label)
                                    <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $company->fiscal_year_start_month) === $month)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fiscal_year_start_month')" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Save Changes') }}</x-button>
                        <a href="{{ route('superadmin.companies.show', $company) }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
