<div class="flex items-center justify-end" style="margin-bottom:14px">
    @can('taxation.create')
        <button type="button" class="tx-btn tx-btn-cta" @click="$dispatch('tax-toggle-rate')">
            + {{ __('New Rate') }}
        </button>
    @endcan
</div>

<div class="tx-card" x-data="{ open: false }" x-show="open" x-on:tax-toggle-rate.window="open = !open" x-cloak>
    <div class="tx-card-h">
        <span class="ic">&#128202;</span>
        <h2>{{ __('Add Tax Rate') }}</h2>
    </div>
    <div class="tx-pad">
        <form method="POST" action="{{ route('accounting.taxation.rates.store') }}">
            @csrf
            <div class="tx-form-grid">
                <div>
                    <label class="input-label">{{ __('Tax Code') }} <span class="text-red-600">*</span></label>
                    <select name="tax_code_id" class="input" required>
                        <option value="">{{ __('— Select —') }}</option>
                        @foreach ($taxCodes ?? [] as $tc)
                            <option value="{{ $tc->id }}" @selected((string) old('tax_code_id') === (string) $tc->id)>{{ $tc->code }} — {{ $tc->name }}</option>
                        @endforeach
                    </select>
                    @error('tax_code_id')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="input-label">{{ __('Rate (%)') }} <span class="text-red-600">*</span></label>
                    <input type="number" name="rate_pct" step="0.0001" min="0" max="100" required value="{{ old('rate_pct') }}" class="input">
                    @error('rate_pct')<p class="tx-hint tx-err">{{ $message }}</p>@enderror
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
            </div>
            <div class="flex items-center gap-2 mt-4">
                <button type="submit" class="tx-btn tx-btn-sec">{{ __('Save Rate') }}</button>
                <button type="button" class="tx-btn tx-btn-ghost" @click="open = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>