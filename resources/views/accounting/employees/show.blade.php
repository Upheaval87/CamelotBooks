<x-app-layout>
    <x-slot name="header">{{ __('Employee Detail') }}: {{ $employee->full_name }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" href="{{ route('accounting.employees.edit', $employee) }}">{{ __('Edit') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.employees.index') }}">{{ __('Back to Employees') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            {{-- Personal Information --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Personal Information') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Employee Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->employee_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Full Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date of Birth') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->date_of_birth?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Gender') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->gender ? ucfirst($employee->gender) : '—' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Address') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->address ?? '—' }}</dd>
                    </div>
                </div>
            </div>

            {{-- Employment Information --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Employment Information') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Position') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->position ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Department') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->department ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Hire Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->hire_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Branch') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->branch->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Cost Center') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->costCenter->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($employee->is_active)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </div>

            {{-- Tax & Pension --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Tax & Pension') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Tax ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->tax_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('National ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->national_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Pension Member Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->pension_member_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Pension Scheme ID') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->pension_scheme_id ?? '—' }}</dd>
                    </div>
                </div>
            </div>

            {{-- Bank Details --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Bank Details') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Bank Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->bank_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Bank Account Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->bank_account_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Bank Account Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->bank_account_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Bank Branch Code') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $employee->bank_branch_code ?? '—' }}</dd>
                    </div>
                </div>
            </div>

            {{-- Salary Structure --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Salary Structure') }}</h3>
                @if($employee->currentSalaryStructure)
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Basic Pay') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ format_money($employee->currentSalaryStructure->basic_pay) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Effective From') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $employee->currentSalaryStructure->effective_from?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No salary structure defined.</p>
                @endif
            </div>

            {{-- Payslip Password Status --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payslip Password') }}</h3>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($employee->payslip_password)
                            <span class="status-pill positive">Set</span>
                        @else
                            <span class="status-pill neutral">Not Set</span>
                        @endif
                    </dd>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
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
                                    <td class="text-ink-soft">
                                        {{ $payment->payment_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $payment->payment_type ? ucfirst(str_replace('_', ' ', $payment->payment_type)) : '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($payment->amount) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        No payment history found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
