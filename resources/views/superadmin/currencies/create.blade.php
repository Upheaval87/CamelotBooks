<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('New Currency') }}" description="{{ __('Add a reference currency for new companies.') }}" />

        <form method="POST" action="{{ route('superadmin.currencies.store') }}">
            @csrf

            <x-form-section icon="M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1zm8 9a3 3 0 100-6 3 3 0 000 6zM18 12h.01" title="{{ __('Currency') }}" :columns="2">
                <div class="form-field">
                    <label class="sa-label" for="code">{{ __('Currency Code') }}</label>
                    <input id="code" name="code" type="text" class="sa-input" required maxlength="10" value="{{ old('code') }}" placeholder="e.g. MWK, USD" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="name">{{ __('Currency Name') }}</label>
                    <input id="name" name="name" type="text" class="sa-input" required maxlength="120" value="{{ old('name') }}" placeholder="e.g. Malawian Kwacha" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="symbol">{{ __('Symbol') }}</label>
                    <input id="symbol" name="symbol" type="text" class="sa-input" maxlength="12" value="{{ old('symbol') }}" placeholder="e.g. MK, $, K" />
                    <x-input-error :messages="$errors->get('symbol')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="symbol_position">{{ __('Symbol Position') }}</label>
                    <select id="symbol_position" name="symbol_position" class="sa-input" required>
                        <option value="before" @selected(old('symbol_position', 'before') === 'before')>{{ __('Before amount (e.g. MK 1,000)') }}</option>
                        <option value="after" @selected(old('symbol_position') === 'after')>{{ __('After amount (e.g. 1,000 MK)') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('symbol_position')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="sort_order">{{ __('Sort Order') }}</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" class="sa-input" value="{{ old('sort_order', 0) }}" />
                    <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
                </div>

                <div class="form-field flex items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', true)) />
                        {{ __('Active') }}
                    </label>
                </div>
            </x-form-section>

            <div class="sa-form-actions">
                <a href="{{ route('superadmin.currencies.index') }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="sa-btn sa-btn--primary">{{ __('Save Currency') }}</button>
            </div>
        </form>
    </x-superadmin.layout>

</x-app-layout>
