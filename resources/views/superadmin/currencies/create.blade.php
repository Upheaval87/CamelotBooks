<x-app-layout>
    <x-slot name="header">{{ __('New Currency') }}</x-slot>

    <x-superadmin.layout>
        <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.currencies.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="code">{{ __('Currency Code') }}</x-input-label>
                            <x-text-input id="code" name="code" class="mt-1 block w-full" required maxlength="10"
                                :value="old('code')" placeholder="e.g. MWK, USD" />
                            <x-input-error :messages="$errors->get('code')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="name">{{ __('Currency Name') }}</x-input-label>
                            <x-text-input id="name" name="name" class="mt-1 block w-full" required maxlength="120"
                                :value="old('name')" placeholder="e.g. Malawian Kwacha" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="symbol">{{ __('Symbol') }}</x-input-label>
                            <x-text-input id="symbol" name="symbol" class="mt-1 block w-full" maxlength="12"
                                :value="old('symbol')" placeholder="e.g. MK, $, K" />
                            <x-input-error :messages="$errors->get('symbol')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="symbol_position">{{ __('Symbol Position') }}</x-input-label>
                            <select id="symbol_position" name="symbol_position" class="input mt-1 block w-full" required>
                                <option value="before" @selected(old('symbol_position', 'before') === 'before')>{{ __('Before amount (e.g. MK 1,000)') }}</option>
                                <option value="after" @selected(old('symbol_position') === 'after')>{{ __('After amount (e.g. 1,000 MK)') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('symbol_position')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="sort_order">{{ __('Sort Order') }}</x-input-label>
                            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full"
                                :value="old('sort_order', 0)" />
                            <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
                                    @checked(old('is_active', true)) />
                                {{ __('Active') }}
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn-primary">{{ __('Save Currency') }}</button>
                        <a href="{{ route('superadmin.currencies.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
        </div>
    </x-superadmin.layout>
</x-app-layout>
