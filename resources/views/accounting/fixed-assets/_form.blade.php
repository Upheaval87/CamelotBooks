@php
    $asset = $asset ?? new \App\Models\FixedAssets\FaAsset;
    $isEdit = $isEdit ?? (bool) $asset;
    $formAction = $formAction ?? ($isEdit ? route('accounting.fixed-assets.update', $asset->id) : route('accounting.fixed-assets.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $title = $title ?? ($isEdit ? 'Edit Asset' : 'New Fixed Asset');
    $cancelRoute = $isEdit ? route('accounting.fixed-assets.show', $asset->id) : route('accounting.fixed-assets.register');

    $depMethods = [
        'straight_line' => 'Straight Line',
        'declining_balance' => 'Declining Balance',
        'sum_of_years' => 'Sum of Years Digits',
        'units_of_production' => 'Units of Production',
    ];
@endphp

<x-app-layout>
    <div class="fa-wrap">
        <form method="POST" action="{{ $formAction }}" id="asset-form" enctype="multipart/form-data">
            @csrf
            @if ($formMethod !== 'POST') @method($formMethod) @endif

            {{-- Sticky Head --}}
            <div class="fa-head" style="position:sticky;top:var(--topbar-h,106px);z-index:40;background:rgba(245,247,252,.9);backdrop-filter:blur(12px);padding:.75rem 0;margin-bottom:1.5rem;border-bottom:1px solid var(--line,#e2ecec)">
                <div>
                    <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-.02em;color:var(--ink,#0B2A2D)">{{ $title }}</h1>
                    <p style="font-size:.8125rem;color:var(--muted,#5f7476);margin-top:.25rem">Register a fixed asset with depreciation parameters and GL accounts.</p>
                </div>
                <div class="fa-actions">
                    <a href="{{ $cancelRoute }}" class="fa-btn fa-btn-ghost">Cancel</a>
                    @if ($isEdit)
                        <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="fa-btn fa-btn-ghost">View</a>
                    @endif
                    <button type="submit" class="fa-btn fa-btn-primary">
                        {{ $isEdit ? 'Save Changes' : 'Create Asset' }}
                    </button>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div style="background:rgba(185,28,28,.06);border:1px solid rgba(185,28,28,.2);border-radius:12px;padding:1rem;margin-bottom:1.5rem">
                    <ul style="margin:0;padding-left:1.25rem;color:#b91c1c;font-size:.8125rem">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Section 1: Asset Information --}}
            <div class="fa-card">
                <div class="fa-sec">
                    <div class="fa-sec-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
                    </div>
                    <span class="fa-sec-text">Asset Information</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Asset Name <span style="color:#b91c1c">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $asset->name ?? '') }}" class="fa-input" required placeholder="e.g. Office Laptop">
                        @error('name') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Category <span style="color:#b91c1c">*</span></label>
                        <select name="category_id" class="fa-select" required>
                            <option value="">Select category…</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $asset->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->code }} — {{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Class</label>
                        <select name="class_id" class="fa-select">
                            <option value="">None</option>
                            @foreach ($classes as $cls)
                                <option value="{{ $cls->id }}" {{ old('class_id', $asset->class_id ?? '') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                            @endforeach
                        </select>
                        @error('class_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}" class="fa-input" placeholder="e.g. SN-12345">
                        @error('serial_number') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Tag Number</label>
                        <input type="text" name="tag_number" value="{{ old('tag_number', $asset->tag_number ?? '') }}" class="fa-input" placeholder="e.g. TAG-001">
                        @error('tag_number') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Location</label>
                        <input type="text" name="location" value="{{ old('location', $asset->location ?? '') }}" class="fa-input" placeholder="e.g. Head Office — Room 12">
                        @error('location') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Custodian</label>
                        <input type="text" name="custodian" value="{{ old('custodian', $asset->custodian ?? '') }}" class="fa-input" placeholder="e.g. John Doe">
                        @error('custodian') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Vendor</label>
                        <select name="vendor_id" class="fa-select">
                            <option value="">None</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id', $asset->vendor_id ?? '') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="fa-field" style="margin-top:1rem">
                    <label class="fa-label">Description</label>
                    <textarea name="description" class="fa-textarea" rows="3" placeholder="Additional details about this asset…">{{ old('description', $asset->description ?? '') }}</textarea>
                    @error('description') <span class="fa-error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Section 2: Acquisition & Valuation --}}
            <div class="fa-card">
                <div class="fa-sec">
                    <div class="fa-sec-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <span class="fa-sec-text">Acquisition & Valuation</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Acquisition Date <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date?->format('Y-m-d') ?? '') }}" class="fa-input" required>
                        @error('acquisition_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">In-Service Date</label>
                        <input type="date" name="in_service_date" value="{{ old('in_service_date', $asset->in_service_date?->format('Y-m-d') ?? '') }}" class="fa-input">
                        <span class="fa-hint">When the asset started being used.</span>
                        @error('in_service_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Acquisition Cost <span style="color:#b91c1c">*</span></label>
                        <input type="number" name="acquisition_cost" value="{{ old('acquisition_cost', $asset->acquisition_cost ?? '') }}" class="fa-input" step="0.01" min="0" required placeholder="0.00">
                        @error('acquisition_cost') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Residual Value</label>
                        <input type="number" name="residual_value" value="{{ old('residual_value', $asset->residual_value ?? '0') }}" class="fa-input" step="0.01" min="0" placeholder="0.00">
                        @error('residual_value') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Section 3: Depreciation --}}
            <div class="fa-card">
                <div class="fa-sec">
                    <div class="fa-sec-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>
                    </div>
                    <span class="fa-sec-text">Depreciation</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Method <span style="color:#b91c1c">*</span></label>
                        <select name="depreciation_method" class="fa-select" required>
                            <option value="">Select method…</option>
                            @foreach ($depMethods as $key => $label)
                                <option value="{{ $key }}" {{ old('depreciation_method', $asset->depreciation_method ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('depreciation_method') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Useful Life (months) <span style="color:#b91c1c">*</span></label>
                        <input type="number" name="useful_life" value="{{ old('useful_life', $asset->useful_life ?? '') }}" class="fa-input" min="1" required placeholder="e.g. 60">
                        @error('useful_life') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Depreciation Rate (%)</label>
                        <input type="number" name="depreciation_rate" value="{{ old('depreciation_rate', $asset->depreciation_rate ?? '') }}" class="fa-input" step="0.01" min="0" max="100" placeholder="Auto-calculated if blank">
                        <span class="fa-hint">Leave blank for auto-calculation.</span>
                        @error('depreciation_rate') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Section 4: GL Accounts --}}
            <div class="fa-card">
                <div class="fa-sec">
                    <div class="fa-sec-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </div>
                    <span class="fa-sec-text">GL Accounts</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Asset Account <span style="color:#b91c1c">*</span></label>
                        <select name="asset_account_id" class="fa-select" required>
                            <option value="">Select account…</option>
                            @foreach ($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('asset_account_id', $asset->asset_account_id ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('asset_account_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Accumulated Depreciation <span style="color:#b91c1c">*</span></label>
                        <select name="accum_dep_account_id" class="fa-select" required>
                            <option value="">Select account…</option>
                            @foreach ($accumDepAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('accum_dep_account_id', $asset->accum_dep_account_id ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('accum_dep_account_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Depreciation Expense <span style="color:#b91c1c">*</span></label>
                        <select name="dep_expense_account_id" class="fa-select" required>
                            <option value="">Select account…</option>
                            @foreach ($depExpenseAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('dep_expense_account_id', $asset->dep_expense_account_id ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('dep_expense_account_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Disposal Account</label>
                        <select name="disposal_account_id" class="fa-select">
                            <option value="">None</option>
                            @foreach ($disposalAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('disposal_account_id', $asset->disposal_account_id ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        @error('disposal_account_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Section 5: Location & Allocation --}}
            <div class="fa-card">
                <div class="fa-sec">
                    <div class="fa-sec-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <span class="fa-sec-text">Location & Allocation</span>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Branch</label>
                        <select name="branch_id" class="fa-select">
                            <option value="">None</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $asset->branch_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Cost Centre</label>
                        <select name="cost_center_id" class="fa-select">
                            <option value="">None</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" {{ old('cost_center_id', $asset->cost_center_id ?? '') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                            @endforeach
                        </select>
                        @error('cost_center_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
