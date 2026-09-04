<div class="flex items-center justify-end" style="margin-bottom:14px">
    @can('taxation.create')
        <button type="button" class="tx-btn tx-btn-cta" @click="$dispatch('tax-toggle-type')">
            + {{ __('New Tax Type') }}
        </button>
    @endcan
</div>

<div class="tx-card" x-data="{ open: false }" x-show="open" x-on:tax-toggle-type.window="open = !open" x-cloak>
    <div class="tx-card-h">
        <span class="ic">&#128200;</span>
        <h2>{{ __('Add Tax Type') }}</h2>
    </div>
    <div class="tx-pad">
        <form method="POST" action="{{ route('accounting.taxation.types.store') }}">
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
                    <label class="input-label">{{ __('Category') }} <span class="text-red-600">*</span></label>
                    <select name="category" class="input" required>
                        @foreach (['VAT','WHT','PAYE','FBT','CORPORATE','PRESUMPTIVE','OTHER'] as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ __($cat) }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
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
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Save Tax Type') }}</button>
                <button type="button" class="tx-btn tx-btn-ghost" @click="open = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>