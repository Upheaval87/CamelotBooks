<div class="flex items-center justify-end" style="margin-bottom:14px">
    @can('taxation.create')
        <button type="button" class="tx-btn tx-btn-cta" @click="$dispatch('tax-toggle-jurisdiction')">
            + {{ __('New Jurisdiction') }}
        </button>
    @endcan
</div>

<div class="tx-card" x-data="{ open: false }" x-show="open" x-on:tax-toggle-jurisdiction.window="open = !open" x-cloak>
    <div class="tx-card-h">
        <span class="ic">&#127760;</span>
        <h2>{{ __('Add Tax Jurisdiction') }}</h2>
    </div>
    <div class="tx-pad">
        <form method="POST" action="{{ route('accounting.taxation.jurisdictions.store') }}">
            @csrf
            <div class="tx-form-grid">
                <div>
                    <label class="input-label">{{ __('Code') }} <span class="text-red-600">*</span></label>
                    <input type="text" name="code" maxlength="30" required value="{{ old('code') }}" class="input">
                    @error('code')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Name') }} <span class="text-red-600">*</span></label>
                    <input type="text" name="name" maxlength="255" required value="{{ old('name') }}" class="input">
                    @error('name')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Country') }} <span class="text-red-600">*</span></label>
                    <input type="text" name="country" maxlength="100" required value="{{ old('country') }}" class="input">
                    @error('country')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Authority') }} <span class="text-red-600">*</span></label>
                    <input type="text" name="authority" maxlength="200" required value="{{ old('authority') }}" class="input">
                    @error('authority')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Active') }}</label>
                    <select name="active" class="input">
                        <option value="1" @selected(old('active', true) == 1)>{{ __('Active') }}</option>
                        <option value="0" @selected(old('active', false) == 0)>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-4">
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Save Jurisdiction') }}</button>
                <button type="button" class="tx-btn tx-btn-ghost" @click="open = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>