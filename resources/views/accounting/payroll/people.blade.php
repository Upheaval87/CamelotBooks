<x-app-layout>
<div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

    {{-- Breadcrumbs --}}
    <nav class="pr-crumbs mb-4">
        <a href="{{ route('payroll.employees.index') }}">{{ __('Payroll') }}</a>
        <span>›</span>
        <span class="here">{{ __('People Operations') }}</span>
    </nav>

    {{-- Page head --}}
    <div class="pr-page-head">
        <div>
            <h1>{{ __('People Operations') }}</h1>
            <div class="sub">{{ __('Loans with auto-deduction, attendance inputs and leave control.') }}</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="pr-btn pr-btn-ghost pr-btn-sm" disabled title="{{ __('Coming soon') }}">{{ __('🌴 Approve Leave') }}</button>
            <button class="pr-btn pr-btn-ghost pr-btn-sm" disabled title="{{ __('Coming soon') }}">{{ __('Approve OT') }}</button>
            <button class="pr-btn pr-btn-cta pr-btn-sm" @click="activeTab = 'loans'">{{ __('💳 Issue Loan') }}</button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="pr-card" x-data="{ activeTab: 'attendance' }">
        <div class="pr-tabs" role="tablist">
            <button class="pr-tab" :class="activeTab === 'attendance' ? 'on' : ''" @click="activeTab = 'attendance'" role="tab">{{ __('Attendance') }}</button>
            <button class="pr-tab" :class="activeTab === 'leave' ? 'on' : ''" @click="activeTab = 'leave'" role="tab">{{ __('Leave') }}</button>
            <button class="pr-tab" :class="activeTab === 'loans' ? 'on' : ''" @click="activeTab = 'loans'" role="tab">{{ __('Loans') }}</button>
        </div>

        {{-- Attendance tab --}}
        <div class="pr-pad" x-show="activeTab === 'attendance'" x-cloak>
            <div class="pr-formcard">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Attendance Tracking') }}</div>
                    <h1>{{ __('Attendance') }}</h1>
                </div>
                <div class="pr-fc-bd">
                    <div style="text-align:center;padding:40px 20px">
                        <div style="font-size:32px;margin-bottom:12px">📋</div>
                        <p style="font-size:14px;font-weight:700;color:var(--ink);margin-bottom:6px">{{ __('Attendance tracking will be available in a future update.') }}</p>
                        <p style="font-size:12px;color:var(--muted)">{{ __('Biometric import, overtime calculation and late tracking.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Leave tab --}}
        <div class="pr-pad" x-show="activeTab === 'leave'" x-cloak>
            <div class="pr-formcard">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Leave Management') }}</div>
                    <h1>{{ __('Leave') }}</h1>
                </div>
                <div class="pr-fc-bd">
                    <div style="text-align:center;padding:40px 20px">
                        <div style="font-size:32px;margin-bottom:12px">🌴</div>
                        <p style="font-size:14px;font-weight:700;color:var(--ink);margin-bottom:6px">{{ __('Leave management will be available in a future update.') }}</p>
                        <p style="font-size:12px;color:var(--muted)">{{ __('Leave balances, requests, approvals and payroll deductions for unpaid days.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loans tab --}}
        <div class="pr-pad" x-show="activeTab === 'loans'" x-cloak>
            {{-- Active loans table --}}
            <div class="pr-card" style="margin-bottom:20px">
                <div class="pr-card-h">
                    <h2>{{ __('Active Loans') }}</h2>
                </div>
                @if($loans->count())
                    <div class="pr-li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Employee') }}</th>
                                    <th>{{ __('Loan #') }}</th>
                                    <th class="num">{{ __('Principal') }}</th>
                                    <th class="num">{{ __('Outstanding') }}</th>
                                    <th class="num">{{ __('Monthly Deduction') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th style="width:100px">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loans as $loan)
                                    <tr>
                                        <td style="font-weight:700;color:var(--ink)">{{ $loan->employee->first_name ?? '—' }} {{ $loan->employee->last_name ?? '' }}</td>
                                        <td class="pr-mono">{{ $loan->loan_number }}</td>
                                        <td class="pr-numr bold">{{ format_number($loan->principal_amount) }}</td>
                                        <td class="pr-numr">{{ format_number($loan->outstanding_balance) }}</td>
                                        <td class="pr-numr">{{ format_number($loan->monthly_deduction) }}</td>
                                        <td>
                                            @php
                                                $loanStatus = match($loan->status) {
                                                    'active' => 'pr-b-act',
                                                    'completed' => 'pr-b-lock',
                                                    'defaulted' => 'pr-b-term',
                                                    default => 'pr-b-pend',
                                                };
                                            @endphp
                                            <span class="pr-badge {{ $loanStatus }}"><span class="pr-bdot"></span>{{ ucfirst($loan->status) }}</span>
                                        </td>
                                        <td>
                                            <div class="pr-row-act">
                                                <button class="pr-ibtn" title="{{ __('View') }}">👁</button>
                                                <button class="pr-ibtn" title="{{ __('Edit') }}">✎</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pr-pad">
                        <p class="pr-em">{{ __('No active loans.') }}</p>
                    </div>
                @endif
            </div>

            {{-- Create Loan form --}}
            <div x-data="{ showForm: false }">
                <button class="pr-btn pr-btn-cta pr-btn-sm" @click="showForm = !showForm" x-show="!showForm">
                    {{ __('＋ Create Loan') }}
                </button>

                <div x-show="showForm" x-cloak>
                    <div class="pr-formcard" style="margin-top:16px">
                        <div class="pr-fc-hd">
                            <div class="kick">{{ __('New Loan') }}</div>
                            <h1>{{ __('Issue a Loan') }}</h1>
                            <div class="sub">{{ __('Loans auto-deduct each payroll run until repaid.') }}</div>
                        </div>
                        <div class="pr-fc-bd">
                            <form method="POST" action="{{ route('payroll.people.store') }}">
                                @csrf
                                <div class="pr-fgrid">
                                    <div class="pr-field">
                                        <label>{{ __('Employee') }} <span class="pr-req">*</span></label>
                                        <select class="pr-field-in" name="employee_id" required>
                                            <option value="">{{ __('Select employee') }}</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_number }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Loan Number') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="text" name="loan_number" required placeholder="{{ __('e.g. LN-0001') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Principal Amount') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="number" name="principal_amount" step="0.01" min="0.01" required placeholder="{{ __('0.00') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Monthly Deduction') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="number" name="monthly_deduction" step="0.01" min="0.01" required placeholder="{{ __('0.00') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Interest Rate (%)') }} <span class="pr-opt">{{ __('optional') }}</span></label>
                                        <input class="pr-field-in" type="number" name="interest_rate" step="0.01" min="0" max="100" placeholder="{{ __('0') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('Start Date') }} <span class="pr-req">*</span></label>
                                        <input class="pr-field-in" type="date" name="start_date" required>
                                    </div>
                                    <div class="pr-field">
                                        <label>{{ __('End Date') }}</label>
                                        <input class="pr-field-in" type="date" name="end_date">
                                    </div>
                                    <div class="pr-field" style="grid-column:1/-1">
                                        <label>{{ __('Notes') }} <span class="pr-opt">{{ __('optional') }}</span></label>
                                        <textarea class="pr-field-in" name="notes" rows="3" style="height:auto;padding:12px 16px;border-radius:14px;resize:vertical" placeholder="{{ __('Loan purpose or conditions...') }}"></textarea>
                                    </div>
                                </div>
                                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
                                    <button type="button" class="pr-btn pr-btn-ghost pr-btn-sm" @click="showForm = false">{{ __('Cancel') }}</button>
                                    <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Save Loan') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</x-app-layout>
