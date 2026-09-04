<div class="flex items-center justify-end" style="margin-bottom:14px">
    @can('taxation.create')
        <button type="button" class="tx-btn tx-btn-cta" @click="$dispatch('tax-toggle-exemption')">
            + {{ __('New Exemption') }}
        </button>
    @endcan
</div>

<div class="tx-card" x-data="{ open: false }" x-show="open" x-on:tax-toggle-exemption.window="open = !open" x-cloak>
    <div class="tx-card-h">
        <span class="ic">&#10004;</span>
        <h2>{{ __('Add Tax Exemption') }}</h2>
    </div>
    <div class="tx-pad">
        <form method="POST" action="{{ route('accounting.taxation.exemptions.store') }}">
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
                    <label class="input-label">{{ __('Tax Type') }} <span class="text-red-600">*</span></label>
                    <select name="tax_type_id" class="input" required>
                        <option value="">{{ __('— Select —') }}</option>
                        @foreach ($taxTypes ?? [] as $tt)
                            <option value="{{ $tt->id }}" @selected((string) old('tax_type_id') === (string) $tt->id)>{{ $tt->code }} — {{ $tt->name }}</option>
                        @endforeach
                    </select>
                    @error('tax_type_id')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Scope') }} <span class="text-red-600">*</span></label>
                    <select name="scope" class="input" required>
                        @foreach (['SALES','PURCHASES','BOTH'] as $scope)
                            <option value="{{ $scope }}" @selected(old('scope') === $scope)>{{ __(ucfirst(strtolower($scope))) }}</option>
                        @endforeach
                    </select>
                    @error('scope')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Effective From') }} <span class="text-red-600">*</span></label>
                    <input type="date" name="effective_from" required value="{{ old('effective_from') }}" class="input">
                    @error('effective_from')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Effective To') }}</label>
                    <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="input">
                    @error('effective_to')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Active') }}</label>
                    <select name="active" class="input">
                        <option value="1" @selected(old('active', true) == 1)>{{ __('Active') }}</option>
                        <option value="0" @selected(old('active', false) == 0)>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="input-label mt-3">{{ __('Reason') }}</label>
                <textarea name="reason" maxlength="1000" rows="2" class="input">{{ old('reason') }}</textarea>
                @error('reason')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2 mt-4">
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Save Exemption') }}</button>
                <button type="button" class="tx-btn tx-btn-ghost" @click="open = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>