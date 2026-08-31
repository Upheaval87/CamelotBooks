<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('accounting.payroll.dashboard') }}">{{ __('Payroll') }}</a> ›
            <a href="{{ route('accounting.payroll.runs.index') }}">{{ __('Runs') }}</a> ›
            <span class="here">{{ __('New Run') }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ __('New Payroll Run') }}</h1>
                <div class="sub">{{ __('Select the pay period, employees, and statutory tables for this run.') }}</div>
            </div>
            <a href="{{ route('accounting.payroll.runs.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Cancel') }}</a>
        </div>

        <form method="POST" action="{{ route('accounting.payroll.runs.store') }}">
            @csrf

            {{-- Run configuration card --}}
            <div class="pr-formcard" style="margin-bottom:16px">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Run Configuration') }}</div>
                    <h1>{{ __('Period & Settings') }}</h1>
                    <div class="sub">{{ __('Define the pay period and select statutory tables for this run.') }}</div>
                </div>
                <div class="pr-fc-bd">
                    <div class="pr-fgrid">
                        <div class="pr-field">
                            <label>{{ __('Period Start') }} <span class="pr-req">*</span></label>
                            <input type="date" name="pay_period_start" class="pr-field-in" value="{{ old('pay_period_start') }}" required>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Period End') }} <span class="pr-req">*</span></label>
                            <input type="date" name="pay_period_end" class="pr-field-in" value="{{ old('pay_period_end') }}" required>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Payment Date') }} <span class="pr-req">*</span></label>
                            <input type="date" name="payment_date" class="pr-field-in" value="{{ old('payment_date') }}" required>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('PAYE Table') }}</label>
                            <select name="paye_table_id" class="pr-field-in" disabled>
                                @if($payeTable)
                                    <option value="{{ $payeTable->id }}" selected>{{ $payeTable->name }} ({{ $payeTable->year }})</option>
                                @else
                                    <option value="">{{ __('No active PAYE table') }}</option>
                                @endif
                            </select>
                            <div class="pr-hint">{{ __('Active table from Statutory settings — auto-selected.') }}</div>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Pension Scheme') }}</label>
                            <select name="pension_scheme_id" class="pr-field-in" disabled>
                                @if($pensionScheme)
                                    <option value="{{ $pensionScheme->id }}" selected>{{ $pensionScheme->name }}</option>
                                @else
                                    <option value="">{{ __('No active pension scheme') }}</option>
                                @endif
                            </select>
                            <div class="pr-hint">{{ __('Active scheme from Statutory settings — auto-selected.') }}</div>
                        </div>
                        <div class="pr-field">
                            <label>{{ __('Branch') }} <span class="pr-opt">{{ __('optional') }}</span></label>
                            <select name="branch_id" class="pr-field-in">
                                <option value="">{{ __('All Branches') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Employee selection card --}}
            <div class="pr-formcard" style="margin-bottom:16px">
                <div class="pr-fc-hd">
                    <div class="kick">{{ __('Employee Selection') }}</div>
                    <h1>{{ __('Select Employees') }}</h1>
                    <div class="sub">{{ __('Choose which active employees to include in this payroll run.') }}</div>
                </div>
                <div class="pr-fc-bd" style="padding-top:16px">
                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--ink);cursor:pointer">
                            <input type="checkbox" id="select-all-employees">
                            {{ __('Select All') }} ({{ $employees->count() }})
                        </label>
                        @if($employees->count() > 0)
                            <span style="font-size:11px;color:var(--muted)">{{ $employees->count() }} active employees available</span>
                        @endif
                    </div>

                    <div class="pr-li-wrap">
                        <table style="min-width:640px">
                            <thead>
                                <tr>
                                    <th style="width:40px">
                                        <input type="checkbox" id="select-all-employees-head">
                                    </th>
                                    <th>{{ __('Employee #') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Department') }}</th>
                                    <th class="num">{{ __('Basic Pay') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="employee-checkbox" {{ in_array($employee->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                        </td>
                                        <td class="pr-mono">{{ $employee->employee_number }}</td>
                                        <td style="font-weight:600;color:var(--ink)">{{ $employee->full_name }}</td>
                                        <td class="pr-em">{{ $employee->department ?? '—' }}</td>
                                        <td class="pr-numr bold">{{ format_number($employee->currentSalaryStructure?->basic_salary ?? 0) }}</td>
                                        <td><x-payroll::badge :status="$employee->employment_status ?? ($employee->is_active ? 'active' : 'terminated')" /></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">
                                            {{ __('No active employees found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @error('employee_ids')
                        <div style="margin-top:10px;font-size:12px;color:var(--red)">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pr-fc-bar">
                    <span class="pr-fc-lbl">{{ __('Review employee list and submit when ready.') }}</span>
                    <a href="{{ route('accounting.payroll.runs.index') }}" class="pr-btn pr-btn-light">{{ __('← Back') }}</a>
                    <button type="submit" class="pr-btn pr-btn-cta">{{ __('Create Run') }}</button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-employees');
            const selectAllHead = document.getElementById('select-all-employees-head');
            const checkboxes = document.querySelectorAll('.employee-checkbox');

            function syncAll(checked) {
                checkboxes.forEach(function (cb) { cb.checked = checked; });
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    syncAll(this.checked);
                    if (selectAllHead) selectAllHead.checked = this.checked;
                });
            }
            if (selectAllHead) {
                selectAllHead.addEventListener('change', function () {
                    syncAll(this.checked);
                    if (selectAll) selectAll.checked = this.checked;
                });
            }

            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', function () {
                    const total = checkboxes.length;
                    const checked = document.querySelectorAll('.employee-checkbox:checked').length;
                    if (selectAll) selectAll.checked = checked === total;
                    if (selectAllHead) selectAllHead.checked = checked === total;
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
