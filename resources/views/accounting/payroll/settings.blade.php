<x-app-layout>
<div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

    {{-- Breadcrumbs --}}
    <nav class="pr-crumbs mb-4">
        <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Payroll') }}</a>
        <span>›</span>
        <span class="here">{{ __('Settings') }}</span>
    </nav>

    {{-- Page head --}}
    <div class="pr-page-head">
        <div>
            <h1>{{ __('Payroll Settings') }}</h1>
            <div class="sub">{{ __('Configuration for payroll frequency, approvals, allowances and integrations.') }}</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pr-card" x-data="{ activeTab: 'general' }">
        <div class="pr-tabs" role="tablist">
            <button class="pr-tab" :class="activeTab === 'general' ? 'on' : ''" @click="activeTab = 'general'" role="tab">{{ __('General') }}</button>
            <button class="pr-tab" :class="activeTab === 'allowances' ? 'on' : ''" @click="activeTab = 'allowances'" role="tab">{{ __('Allowances') }}</button>
            <button class="pr-tab" :class="activeTab === 'integrations' ? 'on' : ''" @click="activeTab = 'integrations'" role="tab">{{ __('Integrations') }}</button>
        </div>

        {{-- General tab --}}
        <div class="pr-pad" x-show="activeTab === 'general'" x-cloak>
            <div class="pr-formcard">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('General') }}</div>
                    <h1>{{ __('Payroll Configuration') }}</h1>
                    <div class="sub">{{ __('Core payroll settings that control how runs are processed.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <form method="POST" action="{{ route('accounting.payroll.settings.store') }}">
                        @csrf
                        <div class="pr-fgrid">
                            <div class="pr-field">
                                <label>{{ __('Payroll Frequency') }} <span class="pr-req">*</span></label>
                                <select class="pr-field-in" name="payroll_frequency" required>
                                    <option value="monthly" {{ old('payroll_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                    <option value="bi_weekly" {{ old('payroll_frequency') === 'bi_weekly' ? 'selected' : '' }}>{{ __('Bi-weekly') }}</option>
                                    <option value="weekly" {{ old('payroll_frequency') === 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                </select>
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Default Bank Account') }}</label>
                                <select class="pr-field-in" name="default_bank_account_id">
                                    <option value="">{{ __('Select bank account') }}</option>
                                    @if(isset($bankAccounts))
                                        @foreach($bankAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('default_bank_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} ({{ $account->code }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="pr-hint">{{ __('Bank account for salary payments.') }}</div>
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Auto-calculate PAYE') }}</label>
                                <div style="display:flex;align-items:center;gap:10px;height:48px">
                                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--ink)">
                                        <input type="checkbox" name="auto_calculate_paye" value="1" {{ old('auto_calculate_paye', '1') ? 'checked' : '' }} style="width:18px;height:18px">
                                        {{ __('Automatically calculate PAYE on each run') }}
                                    </label>
                                </div>
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Auto-approve Runs Under Threshold') }} <span class="pr-opt">{{ __('optional') }}</span></label>
                                <input class="pr-field-in" type="number" name="auto_approve_threshold" step="0.01" min="0" value="{{ old('auto_approve_threshold') }}" placeholder="{{ __('e.g. 50000') }}">
                                <div class="pr-hint">{{ __('Runs with total net pay below this amount are auto-approved.') }}</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
                            <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Save Settings') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Allowances tab --}}
        <div class="pr-pad" x-show="activeTab === 'allowances'" x-cloak>
            {{-- Existing allowances table --}}
            <div class="pr-card" style="margin-bottom:20px">
                <div class="pr-card-h">
                    <h2>{{ __('Company Allowances') }}</h2>
                </div>
                @if(isset($allowances) && $allowances->count())
                    <div class="pr-li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('GL Account') }}</th>
                                    <th>{{ __('Taxable') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th style="width:100px">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allowances as $allowance)
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ $allowance->name }}</td>
                                        <td class="pr-mono">{{ $allowance->code }}</td>
                                        <td>
                                            @if($allowance->type === 'allowance')
                                                <span class="pr-tchip pr-tchip-green">{{ __('Allowance') }}</span>
                                            @else
                                                <span class="pr-tchip pr-tchip-amber">{{ __('Deduction') }}</span>
                                            @endif
                                        </td>
                                        <td class="pr-em">{{ $allowance->account?->code ?? '—' }}</td>
                                        <td>
                                            @if($allowance->is_taxable)
                                                <span class="pr-badge pr-b-act"><span class="pr-bdot"></span>{{ __('Yes') }}</span>
                                            @else
                                                <span class="pr-badge pr-b-lock"><span class="pr-bdot"></span>{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($allowance->is_active)
                                                <span class="pr-badge pr-b-act"><span class="pr-bdot"></span>{{ __('Active') }}</span>
                                            @else
                                                <span class="pr-badge pr-b-lock"><span class="pr-bdot"></span>{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="pr-row-act">
                                                <button class="pr-ibtn" title="{{ __('Edit') }}">✎</button>
                                                <button class="pr-ibtn" title="{{ __('Delete') }}" style="color:var(--red)">✕</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pr-pad">
                        <p class="pr-em">{{ __('No allowances configured yet.') }}</p>
                    </div>
                @endif
            </div>

            {{-- Add Allowance form --}}
            <div x-data="{ showForm: false }">
                <button class="pr-btn pr-btn-cta pr-btn-sm" @click="showForm = !showForm" x-show="!showForm">
                    {{ __('＋ Add Allowance') }}
                </button>

                <div x-show="showForm" x-cloak>
                    <div class="pr-formcard" style="margin-top:16px">
                        <div class="pr-fc-hd">
                            <div class="kick">{{ __('New Allowance / Deduction') }}</div>
                            <h1>{{ __('Add Allowance') }}</h1>
                        </div>
                        <div class="pr-fc-bd">
                            <form method="POST" action="{{ route('accounting.payroll.settings.store') }}">
                                @csrf
                                <input type="hidden" name="form_type" value="allowance">
                                <div class="pr-fgrid">
                                    <div class="pr-field">
                                        <label>{{ __('Name') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="text" name="allowance_name" required placeholder="{{ __('e.g. Housing Allowance') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Code') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="text" name="allowance_code" required placeholder="{{ __('e.g. HSG') }}" style="text-transform:uppercase">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Type') }} <span class="pr-req">*</span></label>
                                        <select class="pr-field-in" name="allowance_type" required>
                                            <option value="allowance">{{ __('Allowance') }}</option>
                                            <option value="deduction">{{ __('Deduction') }}</option>
                                        </select>
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('GL Account') }} <span class="pr-req">*</span></label>
                                        <select class="pr-field-in" name="gl_account_id" required>
                                            <option value="">{{ __('Select account') }}</option>
                                            @if(isset($glAccounts))
                                                @foreach($glAccounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Taxable') }}</label>
                                        <div style="display:flex;align-items:center;gap:10px;height:48px">
                                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--ink)">
                                                <input type="checkbox" name="is_taxable" value="1" style="width:18px;height:18px">
                                                {{ __('Subject to PAYE tax') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
                                    <button type="button" class="pr-btn pr-btn-ghost pr-btn-sm" @click="showForm = false">{{ __('Cancel') }}</button>
                                    <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Save Allowance') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Integrations tab --}}
        <div class="pr-pad" x-show="activeTab === 'integrations'" x-cloak>
            <div class="pr-formcard">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Integrations') }}</div>
                    <h1>{{ __('Integration Settings') }}</h1>
                </div>
                <div class="pr-fc-bd">
                    <div style="text-align:center;padding:40px 20px">
                        <div style="font-size:32px;margin-bottom:12px">🔗</div>
                        <p style="font-size:14px;font-weight:700;color:var(--ink);margin-bottom:6px">{{ __('Integration settings will be available in a future update.') }}</p>
                        <p style="font-size:12px;color:var(--muted)">{{ __('Bank file exports, pension provider sync and tax authority filing.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</x-app-layout>
