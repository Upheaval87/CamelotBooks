<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Employee') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.employees.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Personal Information')" />

                            <div>
                                <x-input-label for="employee_number" value="{{ __('Employee Number') }}" />
                                <x-text-input id="employee_number" name="employee_number" type="text" class="mt-1 block w-full" :value="old('employee_number')" required autofocus />
                                <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-3 gap-6 mt-6">
                                <div>
                                    <x-input-label for="first_name" value="{{ __('First Name') }}" />
                                    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name')" required />
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="middle_name" value="{{ __('Middle Name') }}" />
                                    <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name')" />
                                    <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="last_name" value="{{ __('Last Name') }}" />
                                    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name')" required />
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="email" value="{{ __('Email') }}" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="phone" value="{{ __('Phone') }}" />
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="date_of_birth" value="{{ __('Date of Birth') }}" />
                                    <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" />
                                    <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="gender" value="{{ __('Gender') }}" />
                                    <select id="gender" name="gender" class="input mt-1">
                                        <option value="">Select Gender</option>
                                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                        <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="address" value="{{ __('Address') }}" />
                                <textarea id="address" name="address" rows="3" class="input mt-1">{{ old('address') }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            <x-form.section number="02" :title="__('Employment Information')" />

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="position" value="{{ __('Position') }}" />
                                    <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position')" />
                                    <x-input-error :messages="$errors->get('position')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="department" value="{{ __('Department') }}" />
                                    <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department')" />
                                    <x-input-error :messages="$errors->get('department')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-input-label for="hire_date" value="{{ __('Hire Date') }}" />
                                <x-text-input id="hire_date" name="hire_date" type="date" class="mt-1 block w-full" :value="old('hire_date')" required />
                                <x-input-error :messages="$errors->get('hire_date')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                                    <select id="branch_id" name="branch_id" class="input mt-1">
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="cost_center_id" value="{{ __('Cost Center') }}" />
                                    <select id="cost_center_id" name="cost_center_id" class="input mt-1">
                                        <option value="">Select Cost Center</option>
                                        @foreach($costCenters as $costCenter)
                                            <option value="{{ $costCenter->id }}" {{ old('cost_center_id') == $costCenter->id ? 'selected' : '' }}>{{ $costCenter->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('cost_center_id')" class="mt-2" />
                                </div>
                            </div>

                            <x-form.section number="03" :title="__('Tax & Pension')" />

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="tax_id" value="{{ __('Tax ID') }}" />
                                    <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id')" />
                                    <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="national_id" value="{{ __('National ID') }}" />
                                    <x-text-input id="national_id" name="national_id" type="text" class="mt-1 block w-full" :value="old('national_id')" />
                                    <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="pension_member_number" value="{{ __('Pension Member Number') }}" />
                                    <x-text-input id="pension_member_number" name="pension_member_number" type="text" class="mt-1 block w-full" :value="old('pension_member_number')" />
                                    <x-input-error :messages="$errors->get('pension_member_number')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="pension_scheme_id" value="{{ __('Pension Scheme ID') }}" />
                                    <x-text-input id="pension_scheme_id" name="pension_scheme_id" type="text" class="mt-1 block w-full" :value="old('pension_scheme_id')" />
                                    <x-input-error :messages="$errors->get('pension_scheme_id')" class="mt-2" />
                                </div>
                            </div>

                            <x-form.section number="04" :title="__('Bank Details')" />

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="bank_name" value="{{ __('Bank Name') }}" />
                                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name')" />
                                    <x-input-error :messages="$errors->get('bank_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="bank_account_number" value="{{ __('Bank Account Number') }}" />
                                    <x-text-input id="bank_account_number" name="bank_account_number" type="text" class="mt-1 block w-full" :value="old('bank_account_number')" />
                                    <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="bank_account_name" value="{{ __('Bank Account Name') }}" />
                                    <x-text-input id="bank_account_name" name="bank_account_name" type="text" class="mt-1 block w-full" :value="old('bank_account_name')" />
                                    <x-input-error :messages="$errors->get('bank_account_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="bank_branch_code" value="{{ __('Bank Branch Code') }}" />
                                    <x-text-input id="bank_branch_code" name="bank_branch_code" type="text" class="mt-1 block w-full" :value="old('bank_branch_code')" />
                                    <x-input-error :messages="$errors->get('bank_branch_code')" class="mt-2" />
                                </div>
                            </div>

                            <x-form.section number="05" :title="__('Compensation')" />

                            <div class="mt-6">
                                <x-input-label for="basic_pay" value="{{ __('Basic Pay') }}" />
                                <x-text-input id="basic_pay" name="basic_pay" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('basic_pay')" />
                                <x-input-error :messages="$errors->get('basic_pay')" class="mt-2" />
                            </div>

                            <div class="mt-6">
                                <x-input-label for="payslip_password" value="{{ __('Payslip Password') }}" />
                                <x-text-input id="payslip_password" name="payslip_password" type="password" class="mt-1 block w-full" value="" />
                                <p class="mt-1 text-sm text-gray-500">Leave blank to auto-generate based on company policy.</p>
                                <x-input-error :messages="$errors->get('payslip_password')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.employees.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button>{{ __('Create Employee') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Payroll Run'), 'route' => route('accounting.payroll.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Employee List'), 'route' => route('accounting.employees.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
