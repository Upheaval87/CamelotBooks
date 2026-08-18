<x-app-layout>
<div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

    {{-- Breadcrumbs --}}
    <nav class="pr-crumbs mb-4">
        <a href="{{ route('payroll.employees.index') }}">{{ __('Payroll') }}</a>
        <span>›</span>
        <span class="here">{{ __('Reports') }}</span>
    </nav>

    {{-- Page head --}}
    <div class="pr-page-head">
        <div>
            <h1>{{ __('Payroll Reports') }}</h1>
            <div class="sub">{{ __('Employee, payroll, statutory and accounting reports.') }}</div>
        </div>
    </div>

    {{-- Report cards grid --}}
    <div class="pr-repcards">

        <a href="{{ route('accounting.reports.payroll-summary') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Payroll Summary Report') }}</span>
            <span class="d">{{ __('Gross to net breakdown for a payroll period.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.paye-remittance') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('PAYE Remittance Report') }}</span>
            <span class="d">{{ __('Tax deducted per employee for statutory remittance.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.pension-remittance') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Pension Remittance Report') }}</span>
            <span class="d">{{ __('Employer and employee pension contributions by scheme.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.employee-cost-by-branch') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Employee Cost by Branch') }}</span>
            <span class="d">{{ __('Payroll cost allocation across branches and cost centres.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.payslip-report') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Payslip Report') }}</span>
            <span class="d">{{ __('Detailed payslip summary for all employees in a period.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.payroll-audit') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Payroll Audit Trail') }}</span>
            <span class="d">{{ __('Change log for payroll runs, approvals and adjustments.') }}</span>
        </a>

        <a href="{{ route('accounting.reports.tax-depreciation-schedule') }}" class="pr-repcard" style="text-decoration:none">
            <span class="pr-fmt">PDF</span>
            <span class="t">{{ __('Tax Depreciation Schedule') }}</span>
            <span class="d">{{ __('Fixed asset depreciation for tax reporting periods.') }}</span>
        </a>

    </div>

</div>
</x-app-layout>
