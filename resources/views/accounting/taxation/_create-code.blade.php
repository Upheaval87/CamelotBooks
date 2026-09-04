<div class="flex items-center justify-end" style="margin-bottom:14px">
    @can('taxation.create')
        <button type="button" class="tx-btn tx-btn-cta" @click="$dispatch('tax-toggle-code')">
            + {{ __('New Tax Code') }}
        </button>
    @endcan
</div>

<div class="tx-card" x-data="{ open: false }" x-show="open" x-on:tax-toggle-code.window="open = !open" x-cloak>
    <div class="tx-card-h">
        <span class="ic">&#9876;</span>
        <h2>{{ __('Add Tax Code') }}</h2>
    </div>
    <div class="tx-pad">
        <form method="POST" action="{{ route('accounting.taxation.codes.store') }}">
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
                    <label class="input-label">{{ __('Jurisdiction') }}</label>
                    <select name="jurisdiction_id" class="input">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($jurisdictions ?? [] as $jj)
                            <option value="{{ $jj->id }}" @selected((string) old('jurisdiction_id') === (string) $jj->id)>{{ $jj->code }} — {{ $jj->name }}</option>
                        @endforeach
                    </select>
                    @error('jurisdiction_id')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Treatment') }} <span class="text-red-600">*</span></label>
                    <select name="treatment" class="input" required>
                        @foreach (['STANDARD','ZERO_RATED','EXEMPT','DEDUCTED','CHARGED','REVERSE_CHARGE'] as $tr)
                            <option value="{{ $tr }}" @selected(old('treatment', 'STANDARD') === $tr)>{{ __(ucwords(strtolower(str_replace('_',' ',$tr)))) }}</option>
                        @endforeach
                    </select>
                    @error('treatment')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Price Basis') }} <span class="text-red-600">*</span></label>
                    <select name="price_basis" class="input" required>
                        @foreach (['EXCLUSIVE','INCLUSIVE'] as $pb)
                            <option value="{{ $pb }}" @selected(old('price_basis', 'EXCLUSIVE') === $pb)>{{ __(ucfirst(strtolower($pb))) }}</option>
                        @endforeach
                    </select>
                    @error('price_basis')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Rounding Mode') }} <span class="text-red-600">*</span></label>
                    <select name="rounding_mode" class="input" required>
                        @foreach (['HALF_UP','HALF_DOWN','HALF_EVEN'] as $rm)
                            <option value="{{ $rm }}" @selected(old('rounding_mode', 'HALF_UP') === $rm)>{{ __(ucwords(strtolower(str_replace('_',' ',$rm)))) }}</option>
                        @endforeach
                    </select>
                    @error('rounding_mode')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Rounding Level') }} <span class="text-red-600">*</span></label>
                    <select name="rounding_level" class="input" required>
                        @foreach (['LINE','DOCUMENT'] as $rl)
                            <option value="{{ $rl }}" @selected(old('rounding_level', 'LINE') === $rl)>{{ __(ucfirst(strtolower($rl))) }}</option>
                        @endforeach
                    </select>
                    @error('rounding_level')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Effective From') }} <span class="text-red-600">*</span></label>
                    <input type="date" name="effective_from" required value="{{ old('effective_from', date('Y-m-d')) }}" class="input">
                    @error('effective_from')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Effective To') }}</label>
                    <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="input">
                    @error('effective_to')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('GL Output Account') }}</label>
                    <select name="gl_output_acct" class="input">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($glAccounts ?? [] as $acct)
                            <option value="{{ $acct->id }}" @selected((string) old('gl_output_acct') === (string) $acct->id)>{{ $acct->code }} — {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="input-label">{{ __('GL Input Account') }}</label>
                    <select name="gl_input_acct" class="input">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($glAccounts ?? [] as $acct)
                            <option value="{{ $acct->id }}" @selected((string) old('gl_input_acct') === (string) $acct->id)>{{ $acct->code }} — {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="input-label">{{ __('GL Payable Account') }}</label>
                    <select name="gl_payable_acct" class="input">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($glAccounts ?? [] as $acct)
                            <option value="{{ $acct->id }}" @selected((string) old('gl_payable_acct') === (string) $acct->id)>{{ $acct->code }} — {{ $acct->name }}</option>
                        @endforeach
                    </select>
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
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Save Tax Code') }}</button>
                <button type="button" class="tx-btn tx-btn-ghost" @click="open = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>