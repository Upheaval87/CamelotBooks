@php
    $employee = $employee ?? null;
    $isEdit = $isEdit ?? (bool) $employee;
    $formAction = $formAction ?? ($isEdit ? route('accounting.employees.update', $employee) : route('accounting.employees.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? route('accounting.employees.index');
    $title = $title ?? ($isEdit ? __('Edit Employee') : __('Create Employee'));
    $subtitle = $subtitle ?? 'Capture personal, employment, tax, bank and compensation details.';
    $submitLabel = $submitLabel ?? ($isEdit ? __('Update Employee') : __('Create Employee'));

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

    $gender = $isEdit ? old('gender', $employee->gender) : old('gender');
    $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
@endphp

<div class="suite">

    {{-- sticky page head --}}
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub">{{ $subtitle }}</div>
        </div>
        <div class="tbtns">
            @if($isEdit)
                <form method="POST" action="{{ route('accounting.employees.toggle', $employee) }}" id="employee-archive-form" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this employee?') }}', { type: 'danger' })">
                    @csrf @method('PATCH')
                </form>
                <button type="submit" form="employee-archive-form" class="btn danger-o sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                    {{ $employee->is_active ? __('Deactivate') : __('Activate') }}
                </button>
            @endif
            <a href="{{ $cancelRoute }}" class="btn ghost sm">{{ __('Cancel') }}</a>
            <button type="submit" form="employee-form" class="btn cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="employee-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="shell">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- personal information --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>
                        <h2>Personal Information</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field">
                            <label for="employee_number">Employee Number <span class="req">*</span></label>
                            <input id="employee_number" name="employee_number" type="text" class="input" value="{{ $isEdit ? old('employee_number', $employee->employee_number) : old('employee_number') }}" placeholder="e.g. EMP-001" required autofocus />
                            <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="first_name">First Name <span class="req">*</span></label>
                            <input id="first_name" name="first_name" type="text" class="input" value="{{ $isEdit ? old('first_name', $employee->first_name) : old('first_name') }}" placeholder="e.g. Mary" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="middle_name">Middle Name</label>
                            <input id="middle_name" name="middle_name" type="text" class="input" value="{{ $isEdit ? old('middle_name', $employee->middle_name) : old('middle_name') }}" placeholder="e.g. W." />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="last_name">Last Name <span class="req">*</span></label>
                            <input id="last_name" name="last_name" type="text" class="input" value="{{ $isEdit ? old('last_name', $employee->last_name) : old('last_name') }}" placeholder="e.g. Banda" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" class="input" value="{{ $isEdit ? old('email', $employee->email) : old('email') }}" placeholder="name@company.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="text" class="input" value="{{ $isEdit ? old('phone', $employee->phone) : old('phone') }}" placeholder="+265 99 123 4567" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="date_of_birth">Date of Birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" class="input" value="{{ $isEdit ? old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) : old('date_of_birth') }}" />
                            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="input">
                                <option value="">Select gender…</option>
                                @foreach ($genders as $key => $label)
                                    <option value="{{ $key }}" {{ $gender === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>
                        <div class="field sp4">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="2" class="input">{{ $isEdit ? old('address', $employee->address) : old('address') }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- employment information --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v14H3zM16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                        <h2>Employment Information</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="position">Position</label>
                            <input id="position" name="position" type="text" class="input" value="{{ $isEdit ? old('position', $employee->position) : old('position') }}" placeholder="e.g. Sales Associate" />
                            <x-input-error :messages="$errors->get('position')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="department">Department</label>
                            <input id="department" name="department" type="text" class="input" value="{{ $isEdit ? old('department', $employee->department) : old('department') }}" placeholder="e.g. Retail" />
                            <x-input-error :messages="$errors->get('department')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="hire_date">Hire Date <span class="req">*</span></label>
                            <input id="hire_date" name="hire_date" type="date" class="input" value="{{ $isEdit ? old('hire_date', $employee->hire_date?->format('Y-m-d')) : old('hire_date') }}" required />
                            <x-input-error :messages="$errors->get('hire_date')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="branch_id">Branch</label>
                            <x-scoped-search-field
                                name="branch_id"
                                entity="branch"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                :value="$isEdit ? old('branch_id', $employee->branch_id) : old('branch_id')"
                                :label="$isEdit ? (($branches->firstWhere('id', (int) old('branch_id', $employee->branch_id))) ? $branches->firstWhere('id', (int) old('branch_id', $employee->branch_id))->name : '') : (($branches->firstWhere('id', (int) old('branch_id'))) ? $branches->firstWhere('id', (int) old('branch_id'))->name : '')"
                                placeholder="{{ __('Select Branch') }}"
                            />
                            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="cost_center_id">Cost Center</label>
                            <x-scoped-search-field
                                name="cost_center_id"
                                entity="cost-center"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                :value="$isEdit ? old('cost_center_id', $employee->cost_center_id) : old('cost_center_id')"
                                :label="$isEdit ? (($costCenters->firstWhere('id', (int) old('cost_center_id', $employee->cost_center_id))) ? $costCenters->firstWhere('id', (int) old('cost_center_id', $employee->cost_center_id))->name : '') : (($costCenters->firstWhere('id', (int) old('cost_center_id'))) ? $costCenters->firstWhere('id', (int) old('cost_center_id'))->name : '')"
                                placeholder="{{ __('Select Cost Center') }}"
                            />
                            <x-input-error :messages="$errors->get('cost_center_id')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- tax & pension --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 0 1 .665 6.479A11.952 11.952 0 0 0 12 20.055a11.952 11.952 0 0 0-6.824-2.998 12.078 12.078 0 0 1 .665-6.479L12 14z"/></svg></span>
                        <h2>Tax &amp; Pension</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field">
                            <label for="tax_id">Tax ID</label>
                            <input id="tax_id" name="tax_id" type="text" class="input" value="{{ $isEdit ? old('tax_id', $employee->tax_id) : old('tax_id') }}" placeholder="e.g. TX-102938" />
                            <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="national_id">National ID</label>
                            <input id="national_id" name="national_id" type="text" class="input" value="{{ $isEdit ? old('national_id', $employee->national_id) : old('national_id') }}" placeholder="e.g. MW-9912345" />
                            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="pension_member_number">Pension Member Number</label>
                            <input id="pension_member_number" name="pension_member_number" type="text" class="input" value="{{ $isEdit ? old('pension_member_number', $employee->pension_member_number) : old('pension_member_number') }}" />
                            <x-input-error :messages="$errors->get('pension_member_number')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="pension_scheme_id">Pension Scheme ID</label>
                            <input id="pension_scheme_id" name="pension_scheme_id" type="text" class="input" value="{{ $isEdit ? old('pension_scheme_id', $employee->pension_scheme_id) : old('pension_scheme_id') }}" />
                            <x-input-error :messages="$errors->get('pension_scheme_id')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- bank details --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v6m0 0a3 3 0 0 0 6 0M3 13a3 3 0 0 1 6 0m12-6v6m-6 0a3 3 0 0 1 6 0m-6 0a3 3 0 0 0 6 0m-12 6v2m6-2v2"/></svg></span>
                        <h2>Bank Details</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="bank_name">Bank Name</label>
                            <input id="bank_name" name="bank_name" type="text" class="input" value="{{ $isEdit ? old('bank_name', $employee->bank_name) : old('bank_name') }}" placeholder="e.g. National Bank" />
                            <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="bank_account_number">Bank Account Number</label>
                            <input id="bank_account_number" name="bank_account_number" type="text" class="input" value="{{ $isEdit ? old('bank_account_number', $employee->bank_account_number) : old('bank_account_number') }}" placeholder="e.g. 1000 1234 5678" />
                            <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="bank_account_name">Bank Account Name</label>
                            <input id="bank_account_name" name="bank_account_name" type="text" class="input" value="{{ $isEdit ? old('bank_account_name', $employee->bank_account_name) : old('bank_account_name') }}" />
                            <x-input-error :messages="$errors->get('bank_account_name')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="bank_branch_code">Bank Branch Code</label>
                            <input id="bank_branch_code" name="bank_branch_code" type="text" class="input" value="{{ $isEdit ? old('bank_branch_code', $employee->bank_branch_code) : old('bank_branch_code') }}" />
                            <x-input-error :messages="$errors->get('bank_branch_code')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- compensation --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h2>Compensation</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="basic_pay">Basic Pay ({{ $cs }})</label>
                            <input id="basic_pay" name="basic_pay" type="number" step="0.01" min="0" class="input" value="{{ $isEdit ? old('basic_pay', $employee->currentSalaryStructure?->basic_pay ?? '') : old('basic_pay') }}" placeholder="0.00" />
                            <x-input-error :messages="$errors->get('basic_pay')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="payslip_password">Payslip Password</label>
                            <input id="payslip_password" name="payslip_password" type="password" class="input" value="" placeholder="Leave blank to auto-generate" autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('payslip_password')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside>
                <div class="railsum">
                    <div class="card">
                        @if($isEdit)
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                    <h2>Compensation</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist" style="margin-top:12px">
                                    <div class="srow"><span class="l">Basic Pay</span><span class="v">{{ format_number($employee->currentSalaryStructure?->basic_pay ?? 0) }}</span></div>
                                    <div class="srow"><span class="l">Payments</span><span class="v">{{ $employee->payments->count() }}</span></div>
                                </div>
                            </div>
                        @endif

                        <div class="rail-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                <h2>Quick Nav</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="vlist">
                                @if($isEdit)
                                    <a href="{{ route('accounting.employees.show', $employee) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                                        View Employee
                                    </a>
                                    <a href="{{ route('accounting.payroll-runs.create') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-2 0-4 .8-4 2s2 2 4 2 4-.8 4-2-2-2-4-2zm0 0V4m-4 6c0 1.2 1.8 2 4 2s4-.8 4-2m-8 0v6c0 1.2 1.8 2 4 2s4-.8 4-2v-6"/></svg></span>
                                        New Payroll Run
                                    </a>
                                @endif
                                <a href="{{ route('accounting.employees.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                    Employees List
                                </a>
                                <a href="{{ route('accounting.reports.payroll-register') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                        Payroll Register
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>
