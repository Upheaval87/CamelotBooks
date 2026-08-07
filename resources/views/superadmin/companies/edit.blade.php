<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Edit Company') }} - {{ $company->name }}" description="{{ __('Update tenant company details.') }}" />

        <form method="POST" action="{{ route('superadmin.companies.update', $company) }}">
            @csrf
            @method('PATCH')

            {{-- Company Identity --}}
            <x-form-section icon="M3 21h18M6 21V5a2 2 0 012-2h8a2 2 0 012 2v16M14 21v-4h-4v4" title="{{ __('Company Identity') }}" :columns="2">
                <div class="form-field">
                    <label class="sa-label" for="name">{{ __('Company Name') }}</label>
                    <input id="name" name="name" type="text" class="sa-input" required autofocus value="{{ old('name', $company->name) }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="legal_name">{{ __('Legal Name') }}</label>
                    <input id="legal_name" name="legal_name" type="text" class="sa-input" value="{{ old('legal_name', $company->legal_name) }}" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="company_code">{{ __('Company Code') }}</label>
                    <input id="company_code" name="company_code" type="text" class="sa-input" value="{{ old('company_code', $company->company_code) }}" />
                    <x-input-error :messages="$errors->get('company_code')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="tax_id">{{ __('Tax ID') }}</label>
                    <input id="tax_id" name="tax_id" type="text" class="sa-input" value="{{ old('tax_id', $company->tax_id) }}" />
                </div>
            </x-form-section>

            {{-- Contact Information --}}
            <x-form-section icon="M5 4h12a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V4zm4 4h4m-4 4h6m-6 4h3" title="{{ __('Contact Information') }}">
                <div class="form-section-grid" style="--sa-cols: 2">
                    <div class="form-field">
                        <label class="sa-label" for="email">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" class="sa-input" value="{{ old('email', $company->email) }}" />
                    </div>

                    <div class="form-field">
                        <label class="sa-label" for="phone">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" type="text" class="sa-input" value="{{ old('phone', $company->phone) }}" />
                    </div>
                </div>

                <div class="form-field form-field--full" style="margin-top: 20px;">
                    <label class="sa-label" for="address">{{ __('Address') }}</label>
                    <input id="address" name="address" type="text" class="sa-input" value="{{ old('address', $company->address) }}" />
                </div>

                <div class="form-section-grid" style="--sa-cols: 4; margin-top: 20px;">
                    <div class="form-field">
                        <label class="sa-label" for="city">{{ __('City') }}</label>
                        <input id="city" name="city" type="text" class="sa-input" value="{{ old('city', $company->city) }}" />
                    </div>
                    <div class="form-field">
                        <label class="sa-label" for="state">{{ __('State / Province') }}</label>
                        <input id="state" name="state" type="text" class="sa-input" value="{{ old('state', $company->state) }}" />
                    </div>
                    <div class="form-field">
                        <label class="sa-label" for="country">{{ __('Country') }}</label>
                        <input id="country" name="country" type="text" class="sa-input" value="{{ old('country', $company->country) }}" />
                    </div>
                    <div class="form-field">
                        <label class="sa-label" for="postal_code">{{ __('Postal Code') }}</label>
                        <input id="postal_code" name="postal_code" type="text" class="sa-input" value="{{ old('postal_code', $company->postal_code) }}" />
                    </div>
                </div>
            </x-form-section>

            {{-- Financial Configuration --}}
            <x-form-section icon="M4 6c0 1.657 3.582 3 8 3s8-1.343 8-3-3.582-3-8-3-8 1.343-8 3zm0 0v12c0 1.657 3.582 3 8 3 1.26 0 2.44-.15 3.5-.4M20 10v8m-4-4h8M4 12c0 1.657 3.582 3 8 3" title="{{ __('Financial Configuration') }}">
                    <div class="form-section-grid" style="--sa-cols: 2">
                        <div class="form-field">
                            <label class="sa-label" for="base_currency">{{ __('Base Currency') }}</label>
                            <select id="base_currency" name="base_currency" class="sa-input" required>
                                @forelse($currencies as $currency)
                                    <option value="{{ $currency->code }}" @selected(old('base_currency', $company->base_currency) === $currency->code)>{{ $currency->label() }}</option>
                                @empty
                                    <option value="{{ $company->base_currency }}" selected>{{ $company->base_currency }}</option>
                                @endforelse
                            </select>
                            <x-input-error :messages="$errors->get('base_currency')" class="mt-1" />
                        </div>

                        <div class="form-field">
                            <label class="sa-label" for="fiscal_year_start_month">{{ __('Fiscal Year Starts In') }}</label>
                            <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="sa-input" required>
                                @foreach([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $month => $label)
                                    <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $company->fiscal_year_start_month) === $month)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fiscal_year_start_month')" class="mt-1" />
                        </div>
                    </div>
            </x-form-section>

            {{-- Form actions --}}
            <div class="sa-form-actions">
                <a href="{{ route('superadmin.companies.show', $company) }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="sa-btn sa-btn--primary">{{ __('Save Changes') }}</button>
            </div>
        </form>
    </x-superadmin.layout>

</x-app-layout>
