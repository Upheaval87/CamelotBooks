<x-app-layout>
<div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

    {{-- Breadcrumbs --}}
    <nav class="pr-crumbs mb-4">
        <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Payroll') }}</a>
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

        @php
            $payrollReports = [
                ['route' => 'accounting.reports.employee-cost-by-branch', 'title' => 'Employee Cost by Branch', 'desc' => 'Payroll cost allocation across branches and cost centres.'],
                ['route' => 'accounting.reports.pension-remittance', 'title' => 'Pension Remittance Report', 'desc' => 'Employer and employee pension contributions by scheme.'],
                ['route' => 'accounting.reports.tax-depreciation-schedule', 'title' => 'Tax Depreciation Schedule', 'desc' => 'Fixed asset depreciation for tax reporting periods.'],
            ];
        @endphp

        @foreach($payrollReports as $r)
            @if(Route::has($r['route']))
                <a href="{{ route($r['route']) }}" class="pr-repcard" style="text-decoration:none">
                    <span class="pr-fmt">PDF</span>
                    <span class="t">{{ __($r['title']) }}</span>
                    <span class="d">{{ __($r['desc']) }}</span>
                </a>
            @endif
        @endforeach

    </div>

</div>
</x-app-layout>
