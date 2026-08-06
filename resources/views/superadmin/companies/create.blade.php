<x-app-layout>
    <x-slot name="header">{{ __('New Company') }}</x-slot>

    @include('superadmin._nav', ['active' => 'companies'])

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.companies.store') }}" class="space-y-6" x-data="{
                    companyName: '',
                    preview: '',
                    timer: null,
                    debouncedPreview() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.fetchPreview(), 400);
                    },
                    fetchPreview() {
                        if (!this.companyName) { this.preview = ''; return; }
                        fetch('{{ route('superadmin.db-preview') }}?name=' + encodeURIComponent(this.companyName))
                            .then(r => r.json())
                            .then(d => this.preview = d.db_name)
                            .catch(() => this.preview = '');
                    }
                }">
                    @csrf

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="name">{{ __('Company Name') }}</x-input-label>
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required autofocus
                                x-model="companyName" @input="debouncedPreview()"
                                :value="old('name')" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="legal_name">{{ __('Legal Name') }}</x-input-label>
                            <x-text-input id="legal_name" name="legal_name" class="mt-1 block w-full" :value="old('legal_name')" />
                        </div>

                        <div>
                            <x-input-label for="company_code">{{ __('Company Code') }}</x-input-label>
                            <x-text-input id="company_code" name="company_code" class="mt-1 block w-full" :value="old('company_code')" />
                            <x-input-error :messages="$errors->get('company_code')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="tax_id">{{ __('Tax ID') }}</x-input-label>
                            <x-text-input id="tax_id" name="tax_id" class="mt-1 block w-full" :value="old('tax_id')" />
                        </div>

                        <div>
                            <x-input-label for="email">{{ __('Email') }}</x-input-label>
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                        </div>

                        <div>
                            <x-input-label for="phone">{{ __('Phone') }}</x-input-label>
                            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="address">{{ __('Address') }}</x-input-label>
                            <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address')" />
                        </div>

                        <div>
                            <x-input-label for="city">{{ __('City') }}</x-input-label>
                            <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city')" />
                        </div>

                        <div>
                            <x-input-label for="state">{{ __('State / Province') }}</x-input-label>
                            <x-text-input id="state" name="state" class="mt-1 block w-full" :value="old('state')" />
                        </div>

                        <div>
                            <x-input-label for="country">{{ __('Country') }}</x-input-label>
                            <x-text-input id="country" name="country" class="mt-1 block w-full" :value="old('country')" />
                        </div>

                        <div>
                            <x-input-label for="postal_code">{{ __('Postal Code') }}</x-input-label>
                            <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="old('postal_code')" />
                        </div>

                        <div>
                            <x-input-label for="base_currency">{{ __('Base Currency') }}</x-input-label>
                            <select id="base_currency" name="base_currency" class="input mt-1 block w-full" required>
                                @forelse($currencies as $currency)
                                    <option value="{{ $currency->code }}" @selected(old('base_currency', 'MWK') === $currency->code)>{{ $currency->label() }}</option>
                                @empty
                                    <option value="MWK">MWK - Malawian Kwacha</option>
                                @endforelse
                            </select>
                            <x-input-error :messages="$errors->get('base_currency')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="fiscal_year_start_month">{{ __('Fiscal Year Starts In') }}</x-input-label>
                            <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="input mt-1 block w-full" required>
                                @foreach([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $month => $label)
                                    <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', 1) === $month)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fiscal_year_start_month')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="branch_limit">{{ __('Initial Branch Limit') }}</x-input-label>
                            <x-text-input id="branch_limit" name="branch_limit" type="number" min="0" class="mt-1 block w-full" required
                                :value="old('branch_limit', 1)" />
                            <x-input-error :messages="$errors->get('branch_limit')" class="mt-1" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('The maximum number of branches this company can create. 0 = none until raised; a higher limit can be set later via the Branch Limit override.') }}</p>
                        </div>
                    </div>

                    <div class="bg-panel border border-line rounded-lg p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">{{ __('Tenant Database Preview') }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ __('A dedicated tenant database will be created and provisioned on submission.') }}
                        </p>
                        <p class="mt-3 font-mono text-sm text-ink">
                            <span class="text-gray-500">{{ __('Name:') }}</span>
                            <span x-text="preview || '—'">—</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Create & Provision') }}</x-button>
                        <a href="{{ route('superadmin.companies.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
