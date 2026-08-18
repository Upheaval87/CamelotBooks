@php
    $stepTitles = [
        ['name' => __('Personal'), 'sub' => __('Identity & contact')],
        ['name' => __('Employment'), 'sub' => __('Role & placement')],
        ['name' => __('Compensation'), 'sub' => __('Salary & benefits')],
        ['name' => __('Deductions'), 'sub' => __('Pension & allowances')],
        ['name' => __('Banking'), 'sub' => __('Payout details')],
        ['name' => __('Review'), 'sub' => __('Confirm & submit')],
    ];
@endphp

<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6" x-data="payrollOnboard()" x-cloak>

        {{-- Page head --}}
        <div class="pr-fc-hd" style="padding:0 0 0">
            <nav class="pr-crumbs" style="margin-bottom:8px">
                <a href="{{ route('payroll.employees.index') }}">{{ __('Payroll') }}</a>
                <span style="color:var(--faint)">&rsaquo;</span>
                <a href="{{ route('payroll.employees.index') }}">{{ __('Employees') }}</a>
                <span style="color:var(--faint)">&rsaquo;</span>
                <span class="here">{{ __('Add New') }}</span>
            </nav>
            <h1>{{ __('Onboard a new employee') }}</h1>
            <div class="sub">{{ __('Six short steps with smart defaults.') }}</div>
        </div>

        {{-- Stepper --}}
        <div class="pr-hsteps">
            @foreach($stepTitles as $i => $step)
                @php
                    $stepNum = $i + 1;
                    $stateClass = '';
                    if ($stepNum < $currentStep) $stateClass = 'done';
                    elseif ($stepNum === $currentStep) $stateClass = 'cur';
                @endphp
                <div class="pr-hs {{ $stateClass }}" @click="goToStep({{ $stepNum }})" style="cursor:pointer">
                    <span class="pr-hs-dot">
                        @if($stepNum < $currentStep) &#10003; @else {{ $stepNum }} @endif
                    </span>
                    <span>
                        <span class="t">{{ $step['name'] }}</span>
                        <div class="d">{{ $step['sub'] }}</div>
                    </span>
                    @if($stepNum < count($stepTitles))
                        <span class="pr-hs-bar"></span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('payroll.employees.store') }}" id="onboard-form" class="pr-formcard" enctype="multipart/form-data">
            @csrf

            {{-- Step 1: Personal --}}
            <div x-show="step === 1" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 1') }} &middot; {{ __('Personal') }}</div>
                    <h1>{{ __('Tell us about this person') }}</h1>
                    <div class="sub">{{ __('Basic identity and contact details.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('First Name') }} <span class="pr-req">*</span></label>
                            <input type="text" name="first_name" class="pr-field-in" required value="{{ old('first_name') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Last Name') }} <span class="pr-req">*</span></label>
                            <input type="text" name="last_name" class="pr-field-in" required value="{{ old('last_name') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Middle Name') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="middle_name" class="pr-field-in" value="{{ old('middle_name') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Email') }} <span class="pr-req">*</span></label>
                            <input type="email" name="email" class="pr-field-in" required value="{{ old('email') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Phone') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="phone" class="pr-field-in" value="{{ old('phone') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Date of Birth') }} <span class="pr-opt">optional</span></label>
                            <input type="date" name="date_of_birth" class="pr-field-in" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Gender') }} <span class="pr-opt">optional</span></label>
                            <select name="gender" class="pr-field-in">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                                <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('National ID') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="national_id" class="pr-field-in" value="{{ old('national_id') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Tax ID') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="tax_id" class="pr-field-in" value="{{ old('tax_id') }}">
                        </div>
                        <div class="pr-field" style="grid-column:1/-1">
                            <label>{{ __('Address') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="address" class="pr-field-in" value="{{ old('address') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Employment --}}
            <div x-show="step === 2" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 2') }} &middot; {{ __('Employment') }}</div>
                    <h1>{{ __('Role and placement') }}</h1>
                    <div class="sub">{{ __('Position, department, branch and employment type.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('Position') }} <span class="pr-req">*</span></label>
                            <input type="text" name="position" class="pr-field-in" required value="{{ old('position') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Department') }} <span class="pr-req">*</span></label>
                            <input type="text" name="department" class="pr-field-in" required value="{{ old('department') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Branch') }} <span class="pr-opt">optional</span></label>
                            <select name="branch_id" class="pr-field-in">
                                <option value="">{{ __('Select branch...') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Employment Status') }} <span class="pr-req">*</span></label>
                            <select name="employment_status" class="pr-field-in" required>
                                <option value="active" {{ old('employment_status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="on_leave" {{ old('employment_status') === 'on_leave' ? 'selected' : '' }}>{{ __('On Leave') }}</option>
                                <option value="contract" {{ old('employment_status') === 'contract' ? 'selected' : '' }}>{{ __('Contract') }}</option>
                                <option value="terminated" {{ old('employment_status') === 'terminated' ? 'selected' : '' }}>{{ __('Terminated') }}</option>
                            </select>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Hire Date') }} <span class="pr-req">*</span></label>
                            <input type="date" name="hire_date" class="pr-field-in" required value="{{ old('hire_date', date('Y-m-d')) }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Employment Type') }} <span class="pr-opt">optional</span></label>
                            <select name="employment_type" class="pr-field-in">
                                <option value="">{{ __('Select type...') }}</option>
                                <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>{{ __('Full Time') }}</option>
                                <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>{{ __('Part Time') }}</option>
                                <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>{{ __('Contract') }}</option>
                                <option value="intern" {{ old('employment_type') === 'intern' ? 'selected' : '' }}>{{ __('Intern') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3: Compensation --}}
            <div x-show="step === 3" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 3') }} &middot; {{ __('Compensation') }}</div>
                    <h1>{{ __('Salary and benefits') }}</h1>
                    <div class="sub">{{ __('Set the basic pay and pay frequency.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('Basic Pay') }} <span class="pr-req">*</span></label>
                            <input type="number" name="basic_salary" class="pr-field-in" required min="0" step="0.01" value="{{ old('basic_salary') }}" x-model.number="basicSalary">
                            <div class="pr-hint" x-show="basicSalary > 0">
                                <b>&#10003;</b> {{ __('Monthly') }}: <span x-text="formatMoney(basicSalary)"></span>
                            </div>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Pay Frequency') }} <span class="pr-req">*</span></label>
                            <select name="payment_frequency" class="pr-field-in" required x-model="payFrequency">
                                <option value="monthly" {{ old('payment_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                <option value="weekly" {{ old('payment_frequency') === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                            </select>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Currency') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="currency" class="pr-field-in" value="{{ old('currency', 'MWK') }}" placeholder="MWK" readonly>
                            <div class="pr-hint">{{ __('System currency — set in Settings.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4: Deductions --}}
            <div x-show="step === 4" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 4') }} &middot; {{ __('Deductions') }}</div>
                    <h1>{{ __('Pension and allowances') }}</h1>
                    <div class="sub">{{ __('Select pension scheme and applicable allowances.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('Pension Scheme') }} <span class="pr-opt">optional</span></label>
                            <select name="pension_scheme_id" class="pr-field-in">
                                <option value="">{{ __('None') }}</option>
                                @foreach($pensionSchemes as $scheme)
                                    <option value="{{ $scheme->id }}" {{ old('pension_scheme_id') == $scheme->id ? 'selected' : '' }}>
                                        {{ $scheme->name }} ({{ $scheme->employee_contribution_rate }}% {{ __('employee') }} / {{ $scheme->employer_contribution_rate }}% {{ __('employer') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Pension Member No') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="pension_member_number" class="pr-field-in" value="{{ old('pension_member_number') }}">
                        </div>
                    </div>

                    {{-- Allowances --}}
                    @if($allowances->count())
                        <div style="margin-top:24px">
                            <div style="font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:12px">{{ __('Allowances') }}</div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
                                @foreach($allowances as $allowance)
                                    <div style="display:flex;align-items:center;gap:12px;border:1px solid var(--border);border-radius:12px;padding:12px 14px;background:rgba(255,255,255,.85)">
                                        <input type="checkbox" name="allowances[{{ $loop->index }}][allowance_id]" value="{{ $allowance->id }}" id="allow_{{ $allowance->id }}" class="pr-field-in" style="width:auto;height:auto;border-radius:4px;padding:0">
                                        <label for="allow_{{ $allowance->id }}" style="flex:1;margin:0;font-size:13px;color:var(--ink);text-transform:none;letter-spacing:normal;font-weight:600">
                                            {{ $allowance->name }}
                                            @if($allowance->is_taxable)
                                                <span class="pr-tchip pr-tchip-green" style="margin-left:6px">{{ __('Taxable') }}</span>
                                            @endif
                                        </label>
                                        <input type="number" name="allowances[{{ $loop->index }}][amount]" class="pr-field-in" style="width:100px;height:36px;border-radius:8px" min="0" step="0.01" placeholder="0.00" value="{{ old("allowances.{$loop->index}.amount") }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Step 5: Banking --}}
            <div x-show="step === 5" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 5') }} &middot; {{ __('Banking') }}</div>
                    <h1>{{ __('Payout details') }}</h1>
                    <div class="sub">{{ __('Bank account for salary payments.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('Bank Name') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="bank_name" class="pr-field-in" value="{{ old('bank_name') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Account Number') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="bank_account_number" class="pr-field-in" value="{{ old('bank_account_number') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Account Name') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="bank_account_name" class="pr-field-in" value="{{ old('bank_account_name') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Branch Code') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="bank_branch_code" class="pr-field-in" value="{{ old('bank_branch_code') }}">
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Payslip Password') }} <span class="pr-opt">optional</span></label>
                            <input type="text" name="payslip_password" class="pr-field-in" value="{{ old('payslip_password') }}" placeholder="{{ __('Auto-generated if blank') }}">
                            <div class="pr-hint">{{ __('Used to encrypt employee payslips.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 6: Review --}}
            <div x-show="step === 6" x-transition>
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Step 6') }} &middot; {{ __('Review') }}</div>
                    <h1>{{ __('Review and confirm') }}</h1>
                    <div class="sub">{{ __('Please review the information below before submitting.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div style="display:grid;gap:16px">
                        {{-- Personal --}}
                        <div class="pr-card">
                            <div class="pr-card-h" style="cursor:pointer" @click="goToStep(1)">
                                <h2>{{ __('Personal Details') }}</h2>
                                <div class="right"><span class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Edit') }}</span></div>
                            </div>
                            <div class="pr-pad">
                                <div class="pr-g3">
                                    <div class="pr-fld"><div class="l">{{ __('Name') }}</div><div class="v" x-text="formData.first_name + ' ' + formData.last_name"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Email') }}</div><div class="v" x-text="formData.email"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Phone') }}</div><div class="v" x-text="formData.phone || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('DOB') }}</div><div class="v" x-text="formData.date_of_birth || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Gender') }}</div><div class="v" x-text="formData.gender || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('National ID') }}</div><div class="v" x-text="formData.national_id || '—'"></div></div>
                                </div>
                            </div>
                        </div>

                        {{-- Employment --}}
                        <div class="pr-card">
                            <div class="pr-card-h" style="cursor:pointer" @click="goToStep(2)">
                                <h2>{{ __('Employment') }}</h2>
                                <div class="right"><span class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Edit') }}</span></div>
                            </div>
                            <div class="pr-pad">
                                <div class="pr-g3">
                                    <div class="pr-fld"><div class="l">{{ __('Position') }}</div><div class="v" x-text="formData.position || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Department') }}</div><div class="v" x-text="formData.department || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Branch') }}</div><div class="v" x-text="getSelectedText('branch_id') || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Hire Date') }}</div><div class="v" x-text="formData.hire_date || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Status') }}</div><div class="v" x-text="formData.employment_status || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Type') }}</div><div class="v" x-text="formData.employment_type || '—'"></div></div>
                                </div>
                            </div>
                        </div>

                        {{-- Compensation --}}
                        <div class="pr-card">
                            <div class="pr-card-h" style="cursor:pointer" @click="goToStep(3)">
                                <h2>{{ __('Compensation') }}</h2>
                                <div class="right"><span class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Edit') }}</span></div>
                            </div>
                            <div class="pr-pad">
                                <div class="pr-g3">
                                    <div class="pr-fld"><div class="l">{{ __('Basic Pay') }}</div><div class="v" x-text="formatMoney(formData.basic_salary)"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Frequency') }}</div><div class="v" x-text="formData.payment_frequency || '—'"></div></div>
                                </div>
                            </div>
                        </div>

                        {{-- Deductions --}}
                        <div class="pr-card">
                            <div class="pr-card-h" style="cursor:pointer" @click="goToStep(4)">
                                <h2>{{ __('Deductions') }}</h2>
                                <div class="right"><span class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Edit') }}</span></div>
                            </div>
                            <div class="pr-pad">
                                <div class="pr-g3">
                                    <div class="pr-fld"><div class="l">{{ __('Pension Scheme') }}</div><div class="v" x-text="getSelectedText('pension_scheme_id') || '{{ __('None') }}'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Pension Member No') }}</div><div class="v" x-text="formData.pension_member_number || '—'"></div></div>
                                </div>
                            </div>
                        </div>

                        {{-- Banking --}}
                        <div class="pr-card">
                            <div class="pr-card-h" style="cursor:pointer" @click="goToStep(5)">
                                <h2>{{ __('Banking') }}</h2>
                                <div class="right"><span class="pr-btn pr-btn-ghost pr-btn-xs">{{ __('Edit') }}</span></div>
                            </div>
                            <div class="pr-pad">
                                <div class="pr-g3">
                                    <div class="pr-fld"><div class="l">{{ __('Bank') }}</div><div class="v" x-text="formData.bank_name || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Account No') }}</div><div class="v" x-text="formData.bank_account_number || '—'"></div></div>
                                    <div class="pr-fld"><div class="l">{{ __('Account Name') }}</div><div class="v" x-text="formData.bank_account_name || '—'"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="pr-fc-bar">
                <span class="pr-fc-lbl">{{ __('Step') }} <span x-text="step"></span> {{ __('of') }} 6 &middot; <span x-text="Math.round((step / 6) * 100)"></span>% {{ __('complete') }}</span>
                <button type="button" class="pr-btn pr-btn-light" x-show="step > 1" @click="prevStep()">&larr; {{ __('Back') }}</button>
                <button type="button" class="pr-btn pr-btn-sec" x-show="step < 6" @click="nextStep()">{{ __('Save & continue') }} &rarr;</button>
                <button type="submit" class="pr-btn pr-btn-cta" x-show="step === 6">{{ __('Submit Employee') }}</button>
            </div>
        </form>

    </div>

    @push('scripts')
    <script>
        function payrollOnboard() {
            return {
                step: {{ $currentStep ?? 1 }},
                basicSalary: {{ old('basic_salary', 0) }},
                payFrequency: '{{ old('payment_frequency', 'monthly') }}',
                formData: {
                    first_name: @js(old('first_name')),
                    last_name: @js(old('last_name')),
                    middle_name: @js(old('middle_name')),
                    email: @js(old('email')),
                    phone: @js(old('phone')),
                    date_of_birth: @js(old('date_of_birth')),
                    gender: @js(old('gender')),
                    national_id: @js(old('national_id')),
                    tax_id: @js(old('tax_id')),
                    address: @js(old('address')),
                    position: @js(old('position')),
                    department: @js(old('department')),
                    branch_id: @js(old('branch_id')),
                    employment_status: @js(old('employment_status', 'active')),
                    hire_date: @js(old('hire_date', date('Y-m-d'))),
                    employment_type: @js(old('employment_type')),
                    basic_salary: @js(old('basic_salary')),
                    payment_frequency: @js(old('payment_frequency', 'monthly')),
                    pension_scheme_id: @js(old('pension_scheme_id')),
                    pension_member_number: @js(old('pension_member_number')),
                    bank_name: @js(old('bank_name')),
                    bank_account_number: @js(old('bank_account_number')),
                    bank_account_name: @js(old('bank_account_name')),
                    bank_branch_code: @js(old('bank_branch_code')),
                    payslip_password: @js(old('payslip_password')),
                },

                nextStep() {
                    this.syncFormData();
                    if (this.step < 6) this.step++;
                },

                prevStep() {
                    this.syncFormData();
                    if (this.step > 1) this.step--;
                },

                goToStep(n) {
                    if (n <= this.step) {
                        this.syncFormData();
                        this.step = n;
                    }
                },

                syncFormData() {
                    const form = document.getElementById('onboard-form');
                    if (!form) return;
                    const inputs = form.querySelectorAll('input, select, textarea');
                    inputs.forEach(el => {
                        if (el.name && this.formData.hasOwnProperty(el.name)) {
                            this.formData[el.name] = el.value;
                        }
                    });
                    this.basicSalary = parseFloat(form.querySelector('[name="basic_salary"]')?.value) || 0;
                    this.payFrequency = form.querySelector('[name="payment_frequency"]')?.value || 'monthly';
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
