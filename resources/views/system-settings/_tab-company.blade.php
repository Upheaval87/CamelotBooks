<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'company'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="company-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Company Profile') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-company') }}" enctype="multipart/form-data" id="company-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                <h2>{{ __('Company Profile') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Legal and contact information for this company. The logo appears on all printed documents (invoices, statements, payslips).</p>

            <div class="g3">
                <x-settings.field label="Company Name" name="name" type="text" :value="old('name', $company->name)" required />
                <x-settings.field label="Legal Name" name="legal_name" type="text" :value="old('legal_name', $company->legal_name)" />
                <x-settings.field label="Company Code" name="company_code" type="text" :value="old('company_code', $company->company_code)" />
                <x-settings.field label="Tax ID / Registration Number" name="tax_id" type="text" :value="old('tax_id', $company->tax_id)" />
                <div class="sp2">
                    <x-settings.field label="Address" name="address" type="text" :value="old('address', $company->address)" />
                </div>
                <x-settings.field label="City" name="city" type="text" :value="old('city', $company->city)" />
                <x-settings.field label="State / Province" name="state" type="text" :value="old('state', $company->state)" />
                <x-settings.field label="Country" name="country" type="text" :value="old('country', $company->country)" />
                <x-settings.field label="Postal Code" name="postal_code" type="text" :value="old('postal_code', $company->postal_code)" />
                <x-settings.field label="Phone" name="phone" type="text" :value="old('phone', $company->phone)" />
                <x-settings.field label="Email" name="email" type="email" :value="old('email', $company->email)" />
                <x-settings.field label="Website" name="website" type="url" :value="old('website', $company->website ?? '')" placeholder="https://example.com" />
                <x-settings.field label="Fiscal Year Start Month" name="fiscal_year_start_month" type="select" :value="old('fiscal_year_start_month', $company->fiscal_year_start_month)" required>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ old('fiscal_year_start_month', $company->fiscal_year_start_month) == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </x-settings.field>
            </div>
        </div>

        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                <h2>{{ __('Company Logo') }}</h2>
                <div class="rule"></div>
            </div>

            <x-settings.field label="Company Logo" name="logo" type="file" />
            <p class="hint">Appears on invoices, quotations, statements, payslips, and other printed documents. Recommended: 300x80px, PNG or SVG.</p>

            <div class="flex items-start gap-4" style="margin-top: 12px;">
                <div class="shrink-0">
                    @if($company->logo)
                        <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo" class="h-20 w-auto border border-[#dceaea] rounded-lg p-1 bg-white" />
                    @else
                        <div class="h-20 w-32 border-2 border-dashed border-[#dceaea] rounded-lg flex items-center justify-center text-xs text-[#8aa5a7]">No logo</div>
                    @endif
                </div>
                @if($company->logo)
                <label class="inline-flex items-center gap-2 text-xs text-[#dc2626] cursor-pointer">
                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-[#dceaea] accent-[#128f8e]" />
                    Remove current logo
                </label>
                @endif
            </div>
        </div>
    </div>
</form>
