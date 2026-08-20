@php
    $stepTitles = [
        ['name' => __('Personal'), 'sub' => __('Identity, contact & next of kin')],
        ['name' => __('Employment'), 'sub' => __('Role, type & placement')],
        ['name' => __('Compensation'), 'sub' => __('Salary & benefits')],
        ['name' => __('Deductions'), 'sub' => __('Pension & allowances')],
        ['name' => __('Banking'), 'sub' => __('Payout details')],
        ['name' => __('Review'), 'sub' => __('Confirm & update')],
    ];
    $totalSteps = count($stepTitles);
    $e = $employee;
    $cs = $e->currentSalaryStructure;
@endphp

<x-app-layout>
    <div class="wz max-w-8xl mx-auto sm:px-6 lg:px-8 py-6" x-data="payrollEdit()" x-cloak>

        {{-- Breadcrumbs --}}
        <nav class="wz-crumbs" style="margin-bottom:8px">
            <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Payroll') }}</a>
            <span style="color:var(--faint)">&rsaquo;</span>
            <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Employees') }}</a>
            <span style="color:var(--faint)">&rsaquo;</span>
            <span class="here">{{ $e->first_name }} {{ $e->last_name }}</span>
        </nav>

        {{-- Page head --}}
        <div style="margin-bottom:4px;display:flex;align-items:center;gap:12px">
            <h1 style="font-size:22px;font-weight:800;color:var(--ink);margin:0">{{ __('Edit Employee') }} — {{ $e->first_name }} {{ $e->last_name }}</h1>
            <span style="font-size:12px;color:var(--muted)">{{ $e->employee_number }}</span>
        </div>

        {{-- Stepper --}}
        <div id="wz-stepper">
            @foreach($stepTitles as $i => $step)
                @php $s = $i + 1; @endphp
                <div class="wzstep"
                     :class="{ 'wz-done': step > {{ $s }}, 'wz-current': step === {{ $s }} }"
                     @click="goToStep({{ $s }})">
                    <span class="wzc">
                        <svg x-show="step > {{ $s }}" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" style="pointer-events:none">
                            <path d="M3 7.5L5.5 10L11 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="step <= {{ $s }}" x-text="{{ $s }}" style="pointer-events:none"></span>
                    </span>
                    <div>
                        <span class="wzt">{{ $step['name'] }}</span>
                        <span class="wzs">{{ $step['sub'] }}</span>
                    </div>
                    @if($s < $totalSteps)
                        <span class="wzbar"></span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('accounting.payroll.employees.update', $e) }}" id="edit-employee-form" class="wz-formcard" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 1 — Personal Details
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 1 }" x-show="step === 1" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 1') }} &middot; {{ __('Personal') }}</div>
                    <h1>{{ __('Personal details') }}</h1>
                    <div class="sub">{{ __('Basic identity and contact details.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-section-head">
                        <span class="wz-sec-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        {{ __('Identity & Contact') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('First Name') }} <span class="wz-req">*</span></label>
                            <input type="text" name="first_name" class="wz-in" required value="{{ old('first_name', $e->first_name) }}" x-model="fd.first_name">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Last Name') }} <span class="wz-req">*</span></label>
                            <input type="text" name="last_name" class="wz-in" required value="{{ old('last_name', $e->last_name) }}" x-model="fd.last_name">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Middle Name') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="middle_name" class="wz-in" value="{{ old('middle_name', $e->middle_name) }}" x-model="fd.middle_name">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Email') }} <span class="wz-req">*</span></label>
                            <input type="email" name="email" class="wz-in" required value="{{ old('email', $e->email) }}" x-model="fd.email">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Phone') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="phone" class="wz-in" value="{{ old('phone', $e->phone) }}" x-model="fd.phone">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Date of Birth') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="date" name="date_of_birth" class="wz-in" value="{{ old('date_of_birth', optional($e->date_of_birth)->format('Y-m-d')) }}" x-model="fd.date_of_birth">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Gender') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <select name="gender" class="wz-in" x-model="fd.gender">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="male" {{ old('gender', $e->gender) === 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                <option value="female" {{ old('gender', $e->gender) === 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                                <option value="other" {{ old('gender', $e->gender) === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="wz-field">
                            <label>{{ __('National ID №') }} <span class="wz-req">*</span></label>
                            <input type="text" name="national_id" class="wz-in" required value="{{ old('national_id', $e->national_id) }}" x-model="fd.national_id">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Tax ID') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="tax_id" class="wz-in" value="{{ old('tax_id', $e->tax_id) }}" x-model="fd.tax_id">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Nationality') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="nationality" class="wz-in" value="{{ old('nationality', $e->nationality) }}" x-model="fd.nationality">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Marital Status') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <select name="marital_status" class="wz-in" x-model="fd.marital_status">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="single" {{ old('marital_status', $e->marital_status) === 'single' ? 'selected' : '' }}>{{ __('Single') }}</option>
                                <option value="married" {{ old('marital_status', $e->marital_status) === 'married' ? 'selected' : '' }}>{{ __('Married') }}</option>
                                <option value="divorced" {{ old('marital_status', $e->marital_status) === 'divorced' ? 'selected' : '' }}>{{ __('Divorced') }}</option>
                                <option value="widowed" {{ old('marital_status', $e->marital_status) === 'widowed' ? 'selected' : '' }}>{{ __('Widowed') }}</option>
                            </select>
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Dependents') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="dependents" class="wz-in" min="0" value="{{ old('dependents', $e->dependents) }}" x-model="fd.dependents">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Place of Residence') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="place_of_residence" class="wz-in" value="{{ old('place_of_residence', $e->place_of_residence) }}" x-model="fd.place_of_residence">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Home Village') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="home_village" class="wz-in" value="{{ old('home_village', $e->home_village) }}" x-model="fd.home_village">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Home District') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="home_district" class="wz-in" value="{{ old('home_district', $e->home_district) }}" x-model="fd.home_district">
                        </div>
                    </div>

                    {{-- Next of Kin --}}
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        {{ __('Next of Kin') }} <span class="wz-opt" style="margin-left:6px">{{ __('all optional') }}</span>
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Name') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="nok_name" class="wz-in" value="{{ old('nok_name', $e->nok_name) }}" x-model="fd.nok_name">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Relationship') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <select name="nok_relationship" class="wz-in" x-model="fd.nok_relationship">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="spouse" {{ old('nok_relationship', $e->nok_relationship) === 'spouse' ? 'selected' : '' }}>{{ __('Spouse') }}</option>
                                <option value="parent" {{ old('nok_relationship', $e->nok_relationship) === 'parent' ? 'selected' : '' }}>{{ __('Parent') }}</option>
                                <option value="child" {{ old('nok_relationship', $e->nok_relationship) === 'child' ? 'selected' : '' }}>{{ __('Child') }}</option>
                                <option value="sibling" {{ old('nok_relationship', $e->nok_relationship) === 'sibling' ? 'selected' : '' }}>{{ __('Sibling') }}</option>
                                <option value="other" {{ old('nok_relationship', $e->nok_relationship) === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Phone') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="nok_phone" class="wz-in" value="{{ old('nok_phone', $e->nok_phone) }}" x-model="fd.nok_phone">
                        </div>
                    </div>

                    {{-- Beneficiaries --}}
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </span>
                        {{ __('Beneficiaries') }}
                    </div>
                    <div class="wz-ben-wrap">
                        <template x-for="(ben, idx) in beneficiaries" :key="idx">
                            <div class="wz-ben-row">
                                <input type="text" :name="'beneficiaries[' + idx + '][full_name]'" class="wz-in" placeholder="{{ __('Full name') }}" x-model="ben.full_name" style="flex:2">
                                <select :name="'beneficiaries[' + idx + '][relationship]'" class="wz-in" x-model="ben.relationship" style="flex:1.2">
                                    <option value="">{{ __('Relationship') }}</option>
                                    <option value="spouse">{{ __('Spouse') }}</option>
                                    <option value="child">{{ __('Child') }}</option>
                                    <option value="parent">{{ __('Parent') }}</option>
                                    <option value="sibling">{{ __('Sibling') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                                <input type="text" :name="'beneficiaries[' + idx + '][phone]'" class="wz-in" placeholder="{{ __('Phone') }}" x-model="ben.phone" style="flex:1">
                                <div class="wz-ben-pct-wrap">
                                    <input type="number" :name="'beneficiaries[' + idx + '][pct]'" class="wz-in" min="0" max="100" step="0.01" placeholder="%" x-model.number="ben.pct" style="flex:1">
                                    <span style="color:var(--muted);font-size:12px">%</span>
                                </div>
                                <button type="button" class="wz-ben-remove" @click="removeBeneficiary(idx)" x-show="beneficiaries.length > 1" title="{{ __('Remove') }}">&times;</button>
                            </div>
                        </template>
                        <button type="button" class="wz-ben-add" @click="addBeneficiary()">+ {{ __('Add beneficiary') }}</button>
                        <div class="wz-ben-total">
                            {{ __('Total allocated') }}: <strong :class="{ 'wz-ben-over': benTotal > 100, 'wz-ben-ok': benIsValid && hasAnyBeneficiaryData }" x-text="benTotal + '%'"></strong>
                            <span x-show="benTotal > 100" class="wz-ben-warn">{{ __('must not exceed 100%') }}</span>
                            <span x-show="benError && !benIsValid" class="wz-ben-warn" x-text="benError"></span>
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        {{ __('Documents') }}
                    </div>
                    {{-- Existing documents --}}
                    @if($e->documents->count())
                        <div class="wz-docs-existing">
                            @foreach($e->documents as $doc)
                                <div class="wz-doc-exist-row">
                                    <span class="wz-doc-exist-icon">
                                        @if($doc->kind === 'photo')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                        @elseif($doc->kind === 'national_id')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        @endif
                                    </span>
                                    <span class="wz-doc-exist-name">{{ $doc->kindLabel() }}</span>
                                    <span class="wz-doc-exist-size">{{ $doc->formatSize() }}</span>
                                    <label class="wz-doc-exist-del">
                                        <input type="checkbox" name="delete_documents[]" value="{{ $doc->id }}" style="width:auto;height:auto;border-radius:4px;padding:0">
                                        <span style="font-size:11px;color:var(--red-2)">{{ __('Remove') }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="wz-docs-grid" style="margin-top:12px">
                        <div class="wz-doc-tile">
                            <div class="wz-doc-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <div class="wz-doc-label">{{ __('Replace Passport Photo') }}</div>
                            <div class="wz-doc-hint">JPG / PNG &middot; ≤2MB</div>
                            <label class="wz-doc-btn">
                                <input type="file" name="document_photo" accept="image/jpeg,image/png" class="wz-hidden-input" @change="previewFile($event, 'photo')">
                                <span x-text="fd.photo_label || '{{ __('Choose file') }}'"></span>
                            </label>
                        </div>
                        <div class="wz-doc-tile">
                            <div class="wz-doc-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            </div>
                            <div class="wz-doc-label">{{ __('Replace National ID') }}</div>
                            <div class="wz-doc-hint">PDF / JPG / PNG &middot; ≤5MB</div>
                            <label class="wz-doc-btn">
                                <input type="file" name="document_national_id" accept=".pdf,image/jpeg,image/png" class="wz-hidden-input" @change="previewFile($event, 'national_id')">
                                <span x-text="fd.national_id_label || '{{ __('Choose file') }}'"></span>
                            </label>
                        </div>
                        <div class="wz-doc-tile wz-doc-custom">
                            <div class="wz-doc-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                            </div>
                            <input type="text" name="document_custom_1_name" class="wz-in" placeholder="{{ __('Field name') }}" x-model="fd.custom1_name" style="margin-bottom:8px">
                            <label class="wz-doc-btn">
                                <input type="file" name="document_custom_1" accept=".pdf,image/*,.doc,.docx,.xls,.xlsx,.txt,.csv" class="wz-hidden-input" @change="previewFile($event, 'custom1')">
                                <span x-text="fd.custom1_label || '{{ __('Choose file') }}'"></span>
                            </label>
                        </div>
                        <div class="wz-doc-tile wz-doc-custom">
                            <div class="wz-doc-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                            </div>
                            <input type="text" name="document_custom_2_name" class="wz-in" placeholder="{{ __('Field name') }}" x-model="fd.custom2_name" style="margin-bottom:8px">
                            <label class="wz-doc-btn">
                                <input type="file" name="document_custom_2" accept=".pdf,image/*,.doc,.docx,.xls,.xlsx,.txt,.csv" class="wz-hidden-input" @change="previewFile($event, 'custom2')">
                                <span x-text="fd.custom2_label || '{{ __('Choose file') }}'"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 2 — Employment
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 2 }" x-show="step === 2" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 2') }} &middot; {{ __('Employment') }}</div>
                    <h1>{{ __('Role and placement') }}</h1>
                    <div class="sub">{{ __('Position, department, branch and employment type.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-section-head">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                        {{ __('Position & Department') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Position') }} <span class="wz-req">*</span></label>
                            <input type="text" name="position" class="wz-in" required value="{{ old('position', $e->position) }}" x-model="fd.position">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Department') }} <span class="wz-req">*</span></label>
                            <input type="text" name="department" class="wz-in" required value="{{ old('department', $e->department) }}" x-model="fd.department">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Branch') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <select name="branch_id" class="wz-in" x-model="fd.branch_id">
                                <option value="">{{ __('Select branch...') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $e->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        {{ __('Dates & Type') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Start Date') }} <span class="wz-req">*</span></label>
                            <input type="date" name="hire_date" class="wz-in" required value="{{ old('hire_date', optional($e->hire_date)->format('Y-m-d')) }}" x-model="fd.hire_date">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Employment Type') }} <span class="wz-req">*</span></label>
                            <select name="employment_type" class="wz-in" x-model="fd.employment_type">
                                <option value="full_time" {{ old('employment_type', $e->employment_type) === 'full_time' ? 'selected' : '' }}>{{ __('Full Time') }}</option>
                                <option value="part_time" {{ old('employment_type', $e->employment_type) === 'part_time' ? 'selected' : '' }}>{{ __('Part Time') }}</option>
                                <option value="contract" {{ old('employment_type', $e->employment_type) === 'contract' ? 'selected' : '' }}>{{ __('Contract') }}</option>
                                <option value="casual" {{ old('employment_type', $e->employment_type) === 'casual' ? 'selected' : '' }}>{{ __('Casual') }}</option>
                                <option value="temporary" {{ old('employment_type', $e->employment_type) === 'temporary' ? 'selected' : '' }}>{{ __('Temporary') }}</option>
                            </select>
                        </div>
                        <div class="wz-field" x-show="needsEndDate" x-transition>
                            <label>{{ __('End Date') }} <span class="wz-req">*</span></label>
                            <input type="date" name="employment_end_date" class="wz-in" :required="needsEndDate" value="{{ old('employment_end_date', optional($e->employment_end_date)->format('Y-m-d')) }}" x-model="fd.employment_end_date">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Employment Status') }}</label>
                            <select name="employment_status" class="wz-in" x-model="fd.employment_status">
                                <option value="active" {{ old('employment_status', $e->employment_status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="on_leave" {{ old('employment_status', $e->employment_status) === 'on_leave' ? 'selected' : '' }}>{{ __('On Leave') }}</option>
                                <option value="terminated" {{ old('employment_status', $e->employment_status) === 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 3 — Compensation
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 3 }" x-show="step === 3" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 3') }} &middot; {{ __('Compensation') }}</div>
                    <h1>{{ __('Salary and benefits') }}</h1>
                    <div class="sub">{{ __('Set the basic pay and allowances.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-section-head">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        {{ __('Basic Pay') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Basic Salary') }} <span class="wz-req">*</span></label>
                            <input type="number" name="basic_salary" class="wz-in" required min="0" step="0.01" value="{{ old('basic_salary', $cs?->basic_pay) }}" x-model="fd.basic_salary">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Pay Frequency') }} <span class="wz-req">*</span></label>
                            <select name="payment_frequency" class="wz-in" required x-model="fd.payment_frequency">
                                <option value="monthly" {{ old('payment_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                <option value="weekly" {{ old('payment_frequency') === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        {{ __('Allowances') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Housing') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="housing_allowance" class="wz-in" min="0" step="0.01" value="{{ old('housing_allowance') }}" x-model="fd.housing_allowance">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Transport') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="transport_allowance" class="wz-in" min="0" step="0.01" value="{{ old('transport_allowance') }}" x-model="fd.transport_allowance">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Other') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="other_allowances" class="wz-in" min="0" step="0.01" value="{{ old('other_allowances') }}" x-model="fd.other_allowances">
                        </div>
                    </div>
                    @if($allowances->count())
                        <div style="margin-top:24px">
                            <div class="wz-section-head" style="margin-top:0">
                                <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                                {{ __('Company Allowances') }}
                            </div>
                            <div class="wz-allow-grid">
                                @php $currentAllowanceIds = $cs?->items?->pluck('company_allowance_id')?->toArray() ?? []; @endphp
                                @foreach($allowances as $allowance)
                                    @php $existing = $cs?->items?->firstWhere('company_allowance_id', $allowance->id); @endphp
                                    <div class="wz-allow-item">
                                        <input type="checkbox" name="allowances[{{ $loop->index }}][allowance_id]" value="{{ $allowance->id }}" id="allow_edit_{{ $allowance->id }}" style="width:auto;height:auto;border-radius:4px;padding:0" {{ in_array($allowance->id, $currentAllowanceIds) ? 'checked' : '' }}>
                                        <label for="allow_edit_{{ $allowance->id }}" style="flex:1;margin:0;font-size:13px;color:var(--ink);text-transform:none;letter-spacing:normal;font-weight:600">
                                            {{ $allowance->name }}
                                            @if($allowance->is_taxable)
                                                <span class="wz-chip wz-chip-green" style="margin-left:6px">{{ __('Taxable') }}</span>
                                            @endif
                                        </label>
                                        <input type="number" name="allowances[{{ $loop->index }}][amount]" class="wz-in" style="width:100px;height:36px;border-radius:8px" min="0" step="0.01" placeholder="0.00" value="{{ $existing?->amount ?? old("allowances.{$loop->index}.amount") }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 4 — Deductions
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 4 }" x-show="step === 4" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 4') }} &middot; {{ __('Deductions') }}</div>
                    <h1>{{ __('Pension and deductions') }}</h1>
                    <div class="sub">{{ __('Select pension scheme and applicable deductions.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-section-head">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg></span>
                        {{ __('Pension') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Pension Scheme') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <select name="pension_scheme_id" class="wz-in" x-model="fd.pension_scheme_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach($pensionSchemes as $scheme)
                                    <option value="{{ $scheme->id }}" {{ old('pension_scheme_id', $e->pension_scheme_id) == $scheme->id ? 'selected' : '' }}>
                                        {{ $scheme->name }} ({{ $scheme->employee_rate }}% {{ __('employee') }} / {{ $scheme->employer_rate }}% {{ __('employer') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Pension Member No') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="pension_member_number" class="wz-in" value="{{ old('pension_member_number', $e->pension_member_number) }}" x-model="fd.pension_member_number">
                        </div>
                        <div class="wz-field">
                            <label>{{ __('Employee Contribution %') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="pension_contribution" class="wz-in" min="0" max="100" step="0.01" value="{{ old('pension_contribution') }}" x-model="fd.pension_contribution">
                        </div>
                    </div>
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Other Deductions') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Other Deductions') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="number" name="other_deductions" class="wz-in" min="0" step="0.01" value="{{ old('other_deductions') }}" x-model="fd.other_deductions">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 5 — Banking
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 5 }" x-show="step === 5" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 5') }} &middot; {{ __('Banking') }}</div>
                    <h1>{{ __('Payout details') }}</h1>
                    <div class="sub">{{ __('Bank or mobile money account for salary payments.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-section-head">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
                        {{ __('Payment Method') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field" style="grid-column:1/-1">
                            <label>{{ __('Payment Method') }} <span class="wz-req">*</span></label>
                            <select name="payment_method" class="wz-in" required x-model="fd.payment_method">
                                <option value="bank_transfer" {{ old('payment_method', $e->payment_method) === 'bank_transfer' ? 'selected' : '' }}>{{ __('Bank Transfer') }}</option>
                                <option value="mobile_money" {{ old('payment_method', $e->payment_method) === 'mobile_money' ? 'selected' : '' }}>{{ __('Mobile Money') }}</option>
                                <option value="cash" {{ old('payment_method', $e->payment_method) === 'cash' ? 'selected' : '' }}>{{ __('Cash') }}</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="fd.payment_method === 'bank_transfer'" x-transition>
                        <div class="wz-section-head" style="margin-top:24px">
                            <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 10v11"/><path d="M12 10v11"/><path d="M16 10v11"/></svg></span>
                            {{ __('Bank Details') }}
                        </div>
                        <div class="wz-grid">
                            <div class="wz-field"><label>{{ __('Bank Name') }}</label><input type="text" name="bank_name" class="wz-in" value="{{ old('bank_name', $e->bank_name) }}" x-model="fd.bank_name"></div>
                            <div class="wz-field"><label>{{ __('Account Number') }}</label><input type="text" name="bank_account_number" class="wz-in" value="{{ old('bank_account_number', $e->bank_account_number) }}" x-model="fd.bank_account_number"></div>
                            <div class="wz-field"><label>{{ __('Account Name') }}</label><input type="text" name="bank_account_name" class="wz-in" value="{{ old('bank_account_name', $e->bank_account_name) }}" x-model="fd.bank_account_name"></div>
                            <div class="wz-field"><label>{{ __('Branch Code') }}</label><input type="text" name="bank_branch_code" class="wz-in" value="{{ old('bank_branch_code', $e->bank_branch_code) }}" x-model="fd.bank_branch_code"></div>
                        </div>
                    </div>
                    <div x-show="fd.payment_method === 'mobile_money'" x-transition>
                        <div class="wz-section-head" style="margin-top:24px">
                            <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
                            {{ __('Mobile Money') }}
                        </div>
                        <div class="wz-grid">
                            <div class="wz-field"><label>{{ __('Provider') }}</label><input type="text" name="mobile_money_provider" class="wz-in" value="{{ old('mobile_money_provider', $e->mobile_money_provider) }}" x-model="fd.mobile_money_provider"></div>
                            <div class="wz-field"><label>{{ __('Mobile Number') }}</label><input type="text" name="mobile_money_number" class="wz-in" value="{{ old('mobile_money_number', $e->mobile_money_number) }}" x-model="fd.mobile_money_number"></div>
                        </div>
                    </div>
                    <div class="wz-section-head" style="margin-top:24px">
                        <span class="wz-sec-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                        {{ __('Security') }}
                    </div>
                    <div class="wz-grid">
                        <div class="wz-field">
                            <label>{{ __('Payslip Password') }} <span class="wz-opt">{{ __('optional') }}</span></label>
                            <input type="text" name="payslip_password" class="wz-in" value="{{ old('payslip_password', $e->payslip_password) }}" placeholder="{{ __('Auto-generated if blank') }}" x-model="fd.payslip_password">
                            <div class="wz-hint">{{ __('Used to encrypt employee payslips.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 STEP 6 — Review
                 ═══════════════════════════════════════════════════════════════ --}}
            <div class="wzpanel" :class="{ 'wz-on': step === 6 }" x-show="step === 6" x-transition>
                <div class="wz-hd">
                    <div class="kick">{{ __('Step 6') }} &middot; {{ __('Review') }}</div>
                    <h1>{{ __('Review changes') }}</h1>
                    <div class="sub">{{ __('Please review the information below before saving.') }}</div>
                </div>
                <div class="wz-bd">
                    <div class="wz-review-grid">
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(1)"><h2>{{ __('Personal Details') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad"><div class="wz-g3">
                                <div class="wz-fld"><div class="l">{{ __('Name') }}</div><div class="v" x-text="fd.first_name + ' ' + fd.last_name"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Email') }}</div><div class="v" x-text="fd.email"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Phone') }}</div><div class="v" x-text="fd.phone || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('DOB') }}</div><div class="v" x-text="fd.date_of_birth || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Gender') }}</div><div class="v" x-text="fd.gender || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('National ID') }}</div><div class="v" x-text="fd.national_id || '—'"></div></div>
                            </div></div>
                        </div>
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(2)"><h2>{{ __('Employment') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad"><div class="wz-g3">
                                <div class="wz-fld"><div class="l">{{ __('Position') }}</div><div class="v" x-text="fd.position || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Department') }}</div><div class="v" x-text="fd.department || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Start Date') }}</div><div class="v" x-text="fd.hire_date || '—'"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Type') }}</div><div class="v" x-text="fd.employment_type || '—'"></div></div>
                            </div></div>
                        </div>
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(3)"><h2>{{ __('Compensation') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad"><div class="wz-g3">
                                <div class="wz-fld"><div class="l">{{ __('Basic Salary') }}</div><div class="v" x-text="formatMoney(fd.basic_salary)"></div></div>
                                <div class="wz-fld"><div class="l">{{ __('Frequency') }}</div><div class="v" x-text="fd.payment_frequency || '—'"></div></div>
                            </div></div>
                        </div>
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(4)"><h2>{{ __('Deductions') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad"><div class="wz-g3">
                                <div class="wz-fld"><div class="l">{{ __('Pension Scheme') }}</div><div class="v" x-text="getSelectedText('pension_scheme_id') || '{{ __('None') }}'"></div></div>
                            </div></div>
                        </div>
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(5)"><h2>{{ __('Banking') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad"><div class="wz-g3">
                                <div class="wz-fld"><div class="l">{{ __('Payment Method') }}</div><div class="v" x-text="fd.payment_method || '—'"></div></div>
                                <div class="wz-fld" x-show="fd.payment_method === 'bank_transfer'"><div class="l">{{ __('Bank') }}</div><div class="v" x-text="fd.bank_name || '—'"></div></div>
                                <div class="wz-fld" x-show="fd.payment_method === 'bank_transfer'"><div class="l">{{ __('Account No') }}</div><div class="v" x-text="fd.bank_account_number || '—'"></div></div>
                            </div></div>
                        </div>
                        <div class="wz-rcard">
                            <div class="wz-rcard-h" @click="goToStep(1)"><h2>{{ __('Beneficiaries') }}</h2><button type="button" class="wz-edit">{{ __('Edit') }}</button></div>
                            <div class="wz-pad">
                                <template x-if="hasAnyBeneficiaryData">
                                    <div class="wz-g3">
                                        <template x-for="(ben, idx) in beneficiaries.filter(b => b.full_name || b.relationship || b.phone)" :key="idx">
                                            <div class="wz-fld"><div class="l" x-text="ben.full_name || '—'"></div><div class="v" x-text="(ben.relationship || '—') + ' · ' + (ben.pct || 0) + '%'"></div></div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!hasAnyBeneficiaryData">
                                    <div style="color:var(--muted);font-size:13px;padding:8px 0">{{ __('No beneficiaries.') }}</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="wz-bar">
                <span class="wz-lbl">{{ __('Step') }} <span x-text="step"></span> {{ __('of') }} {{ $totalSteps }}</span>
                <div class="wz-bar-right">
                    <button type="button" class="wz-btn wz-btn-ghost" x-show="step > 1" @click="prevStep()">&larr; {{ __('Back') }}</button>
                    <button type="button" class="wz-btn wz-btn-sec" x-show="step < {{ $totalSteps }}" @click="nextStep()">{{ __('Save & continue') }} &rarr;</button>
                    <button type="submit" class="wz-btn wz-btn-cta" x-show="step === {{ $totalSteps }}">{{ __('Save changes') }} &#10003;</button>
                </div>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        function payrollEdit() {
            return {
                step: 1,
                benError: '',

                fd: {
                    first_name: @js(old('first_name', $e->first_name)),
                    last_name: @js(old('last_name', $e->last_name)),
                    middle_name: @js(old('middle_name', $e->middle_name)),
                    email: @js(old('email', $e->email)),
                    phone: @js(old('phone', $e->phone)),
                    date_of_birth: @js(old('date_of_birth', optional($e->date_of_birth)->format('Y-m-d'))),
                    gender: @js(old('gender', $e->gender)),
                    national_id: @js(old('national_id', $e->national_id)),
                    tax_id: @js(old('tax_id', $e->tax_id)),
                    nationality: @js(old('nationality', $e->nationality)),
                    marital_status: @js(old('marital_status', $e->marital_status)),
                    dependents: @js(old('dependents', $e->dependents)),
                    place_of_residence: @js(old('place_of_residence', $e->place_of_residence)),
                    home_village: @js(old('home_village', $e->home_village)),
                    home_district: @js(old('home_district', $e->home_district)),
                    nok_name: @js(old('nok_name', $e->nok_name)),
                    nok_relationship: @js(old('nok_relationship', $e->nok_relationship)),
                    nok_phone: @js(old('nok_phone', $e->nok_phone)),
                    position: @js(old('position', $e->position)),
                    department: @js(old('department', $e->department)),
                    branch_id: @js(old('branch_id', $e->branch_id)),
                    employment_type: @js(old('employment_type', $e->employment_type)),
                    hire_date: @js(old('hire_date', optional($e->hire_date)->format('Y-m-d'))),
                    employment_end_date: @js(old('employment_end_date', optional($e->employment_end_date)->format('Y-m-d'))),
                    employment_status: @js(old('employment_status', $e->employment_status)),
                    basic_salary: @js(old('basic_salary', $cs?->basic_pay)),
                    payment_frequency: @js(old('payment_frequency', 'monthly')),
                    housing_allowance: @js(old('housing_allowance')),
                    transport_allowance: @js(old('transport_allowance')),
                    other_allowances: @js(old('other_allowances')),
                    pension_scheme_id: @js(old('pension_scheme_id', $e->pension_scheme_id)),
                    pension_member_number: @js(old('pension_member_number', $e->pension_member_number)),
                    pension_contribution: @js(old('pension_contribution')),
                    other_deductions: @js(old('other_deductions')),
                    payment_method: @js(old('payment_method', $e->payment_method ?? 'bank_transfer')),
                    bank_name: @js(old('bank_name', $e->bank_name)),
                    bank_account_number: @js(old('bank_account_number', $e->bank_account_number)),
                    bank_account_name: @js(old('bank_account_name', $e->bank_account_name)),
                    bank_branch_code: @js(old('bank_branch_code', $e->bank_branch_code)),
                    mobile_money_provider: @js(old('mobile_money_provider', $e->mobile_money_provider)),
                    mobile_money_number: @js(old('mobile_money_number', $e->mobile_money_number)),
                    payslip_password: @js(old('payslip_password', $e->payslip_password)),
                    photo_label: '',
                    national_id_label: '',
                    custom1_name: '',
                    custom1_label: '',
                    custom2_name: '',
                    custom2_label: '',
                },

                beneficiaries: @js(
                    old('beneficiaries', $e->beneficiaries->map(fn($b) => [
                        'full_name' => $b->full_name,
                        'relationship' => $b->relationship,
                        'phone' => $b->phone,
                        'pct' => $b->pct,
                    ])->toArray()) ?: [['full_name' => '', 'relationship' => '', 'phone' => '', 'pct' => '']]
                ),

                get needsEndDate() {
                    return this.fd.employment_type && this.fd.employment_type !== 'full_time';
                },

                get benTotal() {
                    return this.beneficiaries.reduce((sum, b) => sum + (parseFloat(b.pct) || 0), 0);
                },

                get hasAnyBeneficiaryData() {
                    return this.beneficiaries.some(b =>
                        (b.full_name && b.full_name.trim()) ||
                        (b.relationship && b.relationship.trim()) ||
                        (b.phone && b.phone.trim())
                    );
                },

                get benTotalRounded() {
                    return Math.round(this.benTotal * 100) / 100;
                },

                get benIsValid() {
                    if (!this.hasAnyBeneficiaryData) return true;
                    return this.benTotalRounded === 100;
                },

                addBeneficiary() { this.beneficiaries.push({ full_name: '', relationship: '', phone: '', pct: '' }); },
                removeBeneficiary(idx) { this.beneficiaries.splice(idx, 1); },

                previewFile(event, key) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.fd[key + '_label'] = file.name;
                },

                nextStep() {
                    this.syncFormData();
                    this.benError = '';

                    if (this.step === 1 && this.hasAnyBeneficiaryData && !this.benIsValid) {
                        this.benError = '{{ __("Beneficiary percentages must total 100%.") }}';
                        return;
                    }

                    if (this.step === 1 && !this.hasAnyBeneficiaryData) {
                        const self = this;
                        CB.confirm({
                            type: 'action',
                            title: '{{ __("No beneficiaries added") }}',
                            message: '{{ __("You have not added any beneficiary details. Do you want to continue to the next step without setting up beneficiaries?") }}',
                            confirmLabel: '{{ __("Continue without beneficiaries") }}',
                            cancelLabel: '{{ __("Go back") }}',
                        }).then(function (confirmed) {
                            if (confirmed) self.advanceStep();
                        });
                        return;
                    }

                    this.advanceStep();
                },

                advanceStep() {
                    if (this.step < {{ $totalSteps }}) {
                        this.step++;
                        this.$nextTick(() => {
                            document.getElementById('edit-employee-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                },

                prevStep() {
                    this.syncFormData();
                    if (this.step > 1) {
                        this.step--;
                        this.$nextTick(() => {
                            document.getElementById('edit-employee-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                },

                goToStep(n) {
                    if (n <= this.step) {
                        this.syncFormData();
                        this.step = n;
                        this.$nextTick(() => {
                            document.getElementById('edit-employee-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                },

                syncFormData() {
                    const form = document.getElementById('edit-employee-form');
                    if (!form) return;
                    const inputs = form.querySelectorAll('input:not([type=file]):not([type=checkbox]), select, textarea');
                    inputs.forEach(el => {
                        if (el.name && this.fd.hasOwnProperty(el.name)) {
                            this.fd[el.name] = el.value;
                        }
                    });
                },

                formatMoney(val) {
                    const num = parseFloat(val) || 0;
                    return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                getSelectedText(name) {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (!el || !el.options) return '';
                    return el.options[el.selectedIndex]?.text || '';
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
