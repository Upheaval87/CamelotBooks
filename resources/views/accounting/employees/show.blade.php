@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $fullName = $employee->full_name;
    $initials = trim(collect([$employee->first_name, $employee->last_name])->filter()->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $basicPay = $employee->currentSalaryStructure?->basic_pay ?? 0;
    $payments = $employee->payments;
    $totalPaid = $payments->sum('amount');
@endphp

<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
<div class="suite">

    {{-- page head --}}
    <div class="page-head">
        <div>
            <a href="{{ route('accounting.employees.index') }}" class="backlink">All Employees</a>
            <h1>{{ $employee->full_name }}</h1>
            <div class="sub">{{ $employee->employee_number }} &middot; {{ $employee->position ?? __('No position set') }} &middot; {{ $employee->department ?? __('No department set') }}</div>
        </div>
        <div class="tbtns">
            <a href="{{ route('accounting.employees.edit', $employee) }}" class="btn cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                {{ __('Edit') }}
            </a>
            <form method="POST" action="{{ route('accounting.employees.toggle', $employee) }}" id="employee-archive-form" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ $employee->is_active ? __('Deactivate this employee?') : __('Activate this employee?') }}', { type: 'danger' })">
                @csrf @method('PATCH')
            </form>
            <button type="submit" form="employee-archive-form" class="btn {{ $employee->is_active ? 'danger-o' : 'ghost' }} sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 1 1 0-4h14a2 2 0 1 1 0 4M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8m-9 4h4"/></svg>
                {{ $employee->is_active ? __('Deactivate') : __('Activate') }}
            </button>
        </div>
    </div>

    {{-- profile --}}
    <div class="prof">
        <div class="ava-xl">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <div class="pn" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <h2 style="margin:0">{{ $fullName }}</h2>
                @if($employee->is_active)
                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                @else
                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                @endif
            </div>
            <div class="pmeta">
                <span class="chip-t">{{ $employee->employee_number }}</span>
                <span>{{ $employee->position ?? __('No position set') }}</span>
                <span>{{ $employee->department ?? __('No department set') }}</span>
                <span>{{ $employee->branch?->name }}</span>
            </div>
        </div>
    </div>

    {{-- stats --}}
    <div class="sgrid" style="margin-top:16px">
        <div class="sbox">
            <div class="l">{{ __('Basic Pay') }}</div>
            <div class="v">{{ $cs }} {{ format_number($basicPay) }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Payments') }}</div>
            <div class="v">{{ $payments->count() }}</div>
        </div>
        <div class="sbox">
            <div class="l">{{ __('Total Paid') }}</div>
            <div class="v">{{ $cs }} {{ format_number($totalPaid) }}</div>
        </div>
    </div>

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
                    <div class="field"><div class="label">Employee Number</div><div class="val mono">{{ $employee->employee_number }}</div></div>
                    <div class="field"><div class="label">Full Name</div><div class="val">{{ $employee->full_name }}</div></div>
                    <div class="field"><div class="label">Email</div><div class="val">{{ $employee->email ?? '—' }}</div></div>
                    <div class="field"><div class="label">Phone</div><div class="val">{{ $employee->phone ?? '—' }}</div></div>
                    <div class="field"><div class="label">Date of Birth</div><div class="val">{{ $employee->date_of_birth?->format('M d, Y') ?? '—' }}</div></div>
                    <div class="field"><div class="label">Gender</div><div class="val">{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</div></div>
                    <div class="field sp3"><div class="label">Address</div><div class="val">{{ $employee->address ?? '—' }}</div></div>
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
                    <div class="field"><div class="label">Position</div><div class="val">{{ $employee->position ?? '—' }}</div></div>
                    <div class="field"><div class="label">Department</div><div class="val">{{ $employee->department ?? '—' }}</div></div>
                    <div class="field"><div class="label">Hire Date</div><div class="val">{{ $employee->hire_date?->format('M d, Y') ?? '—' }}</div></div>
                    <div class="field"><div class="label">Branch</div><div class="val">{{ $employee->branch?->name ?? '—' }}</div></div>
                    <div class="field"><div class="label">Cost Center</div><div class="val">{{ $employee->costCenter?->name ?? '—' }}</div></div>
                    <div class="field"><div class="label">Status</div><div class="val">
                        @if($employee->is_active)
                            <span class="badge b-act"><span class="bdot"></span>Active</span>
                        @else
                            <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                        @endif
                    </div></div>
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
                    <div class="field"><div class="label">Tax ID</div><div class="val">{{ $employee->tax_id ?? '—' }}</div></div>
                    <div class="field"><div class="label">National ID</div><div class="val">{{ $employee->national_id ?? '—' }}</div></div>
                    <div class="field"><div class="label">Pension Member Number</div><div class="val">{{ $employee->pension_member_number ?? '—' }}</div></div>
                    <div class="field"><div class="label">Pension Scheme ID</div><div class="val">{{ $employee->pension_scheme_id ?? '—' }}</div></div>
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
                    <div class="field"><div class="label">Bank Name</div><div class="val">{{ $employee->bank_name ?? '—' }}</div></div>
                    <div class="field"><div class="label">Bank Account Number</div><div class="val">{{ $employee->bank_account_number ?? '—' }}</div></div>
                    <div class="field"><div class="label">Bank Account Name</div><div class="val">{{ $employee->bank_account_name ?? '—' }}</div></div>
                    <div class="field"><div class="label">Bank Branch Code</div><div class="val">{{ $employee->bank_branch_code ?? '—' }}</div></div>
                </div>
            </section>

            {{-- salary structure --}}
            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <h2>Salary Structure</h2>
                    <span class="rule"></span>
                </div>
                @if($employee->currentSalaryStructure)
                    <div class="g4">
                        <div class="field"><div class="label">Basic Pay</div><div class="val t-ink">{{ $cs }} {{ format_number($employee->currentSalaryStructure->basic_pay) }}</div></div>
                        <div class="field"><div class="label">Effective From</div><div class="val">{{ $employee->currentSalaryStructure->effective_from?->format('M d, Y') ?? '—' }}</div></div>
                        <div class="field"><div class="label">Payslip Password</div><div class="val">
                            @if($employee->payslip_password)
                                <span class="badge b-act"><span class="bdot"></span>Set</span>
                            @else
                                <span class="badge b-inact">Not Set</span>
                            @endif
                        </div></div>
                    </div>
                @else
                    <p class="empty" style="margin:0">No salary structure defined.</p>
                @endif
            </section>

            {{-- payment history --}}
            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 9V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m2 4h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm7-5v.01M9 13h10"/></svg></span>
                    <h2>Payment History</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="li-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('Payment Number') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="mono">{{ $payment->payment_number }}</td>
                                    <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ $payment->payment_type ? ucfirst(str_replace('_', ' ', $payment->payment_type)) : '—' }}</td>
                                    <td class="right numr">{{ format_number($payment->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="empty">No payment history found.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- right rail --}}
        <aside>
            <div class="railsum">
                <div class="card">
                    <div class="rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                            <h2>Summary</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="vlist" style="margin-top:12px">
                            <div class="srow"><span class="l">Basic Pay</span><span class="v">{{ $cs }} {{ format_number($basicPay) }}</span></div>
                            <div class="srow"><span class="l">Payments</span><span class="v">{{ $payments->count() }}</span></div>
                            <div class="srow gt"><span class="l">Total Paid</span><span class="v">{{ $cs }} {{ format_number($totalPaid) }}</span></div>
                        </div>
                    </div>

                    <div class="rail-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                            <h2>Quick Nav</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="vlist">
                            <a href="{{ route('accounting.employees.edit', $employee) }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></span>
                                Edit Employee
                            </a>
                            <a href="{{ route('accounting.employees.create') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></span>
                                New Employee
                            </a>
                            <a href="{{ route('accounting.payroll-runs.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                Payroll Runs
                            </a>
                            <a href="{{ route('accounting.reports.payroll-register') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8m8 4H8m-2-8h4"/></svg></span>
                                Payroll Register
                            </a>
                            <a href="javascript:void(0)" onclick="window.print()" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></span>
                                Print
                            </a>
                            <a href="{{ route('accounting.employees.index') }}" class="vitem">
                                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg></span>
                                All Employees
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
        </div>
    </div>
</x-app-layout>
