<x-app-layout>
    <x-slot name="header">{{ __('Employee Detail') }}: {{ $employee->full_name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.employees.edit', $employee) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.employees.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Employees') }}
                </a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="detail-page">
                <div class="detail-page-main">

                    {{-- Personal Information --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Personal Information') }}</p>
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Employee Number') }}" strong>{{ $employee->employee_number }}</x-detail-field>
                            <x-detail-field label="{{ __('Full Name') }}" strong>{{ $employee->full_name }}</x-detail-field>
                            <x-detail-field label="{{ __('Email') }}">{{ $employee->email ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Phone') }}">{{ $employee->phone ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Date of Birth') }}">{{ $employee->date_of_birth?->format('M d, Y') ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Gender') }}">{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Address') }}">{{ $employee->address ?? '—' }}</x-detail-field>
                        </div>
                    </div>

                    {{-- Employment Information --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Employment Information') }}</p>
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Position') }}">{{ $employee->position ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Department') }}">{{ $employee->department ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Hire Date') }}">{{ $employee->hire_date?->format('M d, Y') ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Branch') }}">{{ $employee->branch->name ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Cost Center') }}">{{ $employee->costCenter->name ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Status') }}" noBorder>
                                @if($employee->is_active)
                                    <span class="status-pill positive">{{ __('Active') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('Inactive') }}</span>
                                @endif
                            </x-detail-field>
                        </div>
                    </div>

                    {{-- Tax & Pension --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Tax & Pension') }}</p>
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Tax ID') }}">{{ $employee->tax_id ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('National ID') }}">{{ $employee->national_id ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Pension Member Number') }}">{{ $employee->pension_member_number ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Pension Scheme ID') }}">{{ $employee->pension_scheme_id ?? '—' }}</x-detail-field>
                        </div>
                    </div>

                    {{-- Bank Details --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Bank Details') }}</p>
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Bank Name') }}">{{ $employee->bank_name ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Bank Account Number') }}">{{ $employee->bank_account_number ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Bank Account Name') }}">{{ $employee->bank_account_name ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Bank Branch Code') }}">{{ $employee->bank_branch_code ?? '—' }}</x-detail-field>
                        </div>
                    </div>

                    {{-- Salary Structure --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Salary Structure') }}</p>
                        @if($employee->currentSalaryStructure)
                            <div class="detail-grid">
                                <x-detail-field label="{{ __('Basic Pay') }}" strong>{{ format_money($employee->currentSalaryStructure->basic_pay) }}</x-detail-field>
                                <x-detail-field label="{{ __('Effective From') }}">{{ $employee->currentSalaryStructure->effective_from?->format('M d, Y') ?? '—' }}</x-detail-field>
                            </div>
                        @else
                            <p class="text-sm text-ink-soft">{{ __('No salary structure defined.') }}</p>
                        @endif
                    </div>

                    {{-- Payslip Password Status --}}
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Payslip Password') }}" noBorder>
                                @if($employee->payslip_password)
                                    <span class="status-pill positive">{{ __('Set') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('Not Set') }}</span>
                                @endif
                            </x-detail-field>
                        </div>
                    </div>

                    {{-- Payment History --}}
                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Payment History') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>Payment Number</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employee->payments as $payment)
                                        <tr>
                                            <td>
                                                {{ $payment->payment_number }}
                                            </td>
                                            <td>
                                                {{ $payment->payment_date?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td>
                                                {{ $payment->payment_type ? ucfirst(str_replace('_', ' ', $payment->payment_type)) : '—' }}
                                            </td>
                                            <td class="numeric">
                                                {{ format_money($payment->amount) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                No payment history found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.employees.show', $employee), 'icon' => 'payslip', 'title' => __('View')],
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.employees.index'), 'icon' => 'back', 'title' => __('Back to List')],
                    ]],
                ]" />
            </div>

        </div>
    </div>
</x-app-layout>
