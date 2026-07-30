<form method="POST" action="{{ route('system-settings.update-company') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">01 · COMPANY PROFILE</div>
        <div class="settings-section-title">Company Profile</div>
        <p class="settings-section-desc">Legal and contact information for this company. The logo appears on all printed documents (invoices, statements, payslips).</p>
        <hr class="settings-section-divider">
    </div>

    <div class="settings-card">
        <div class="settings-grid">
            <x-settings.field label="Company Name" name="name" type="text" :value="old('name', $company->name)" required />
            <x-settings.field label="Legal Name" name="legal_name" type="text" :value="old('legal_name', $company->legal_name)" />
            <x-settings.field label="Company Code" name="company_code" type="text" :value="old('company_code', $company->company_code)" />
            <x-settings.field label="Tax ID / Registration Number" name="tax_id" type="text" :value="old('tax_id', $company->tax_id)" />
            <div class="md:col-span-2">
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

        {{-- Logo --}}
        <div class="mt-6 pt-6 border-t border-line">
            <x-settings.field label="Company Logo" name="logo" type="file" />
            <p class="settings-field-hint mb-3">Appears on invoices, quotations, statements, payslips, and other printed documents. Recommended: 300x80px, PNG or SVG.</p>
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    @if($company->logo)
                        <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo" class="h-20 w-auto border border-line rounded p-1 bg-white" />
                    @else
                        <div class="h-20 w-32 border-2 border-dashed border-line rounded flex items-center justify-center text-xs text-ink-faint">No logo</div>
                    @endif
                </div>
                @if($company->logo)
                <label class="inline-flex items-center gap-1 text-xs text-brick cursor-pointer">
                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-brick" />
                    Remove current logo
                </label>
                @endif
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save Company Profile</button>
    </div>
</form>
