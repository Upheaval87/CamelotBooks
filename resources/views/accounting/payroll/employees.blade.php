@php
    $statusCounts = \App\Models\Employee::forCompany($companyId)->selectRaw('status, count(*) as cnt')->groupBy('status')->pluck('cnt', 'status');
    $totalAll = $statusCounts->sum();
    $activeCount = $statusCounts->get('active', 0);
    $onLeaveCount = $statusCounts->get('on_leave', 0);
    $contractCount = $statusCounts->get('contract', 0);
    $terminatedCount = $statusCounts->get('terminated', 0);
    $departments = \App\Models\Employee::forCompany($companyId)->whereNotNull('department')->distinct()->pluck('department')->sort()->values();
@endphp

<x-app-layout>
    <div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="pr-crumbs" style="margin-bottom:6px">
            <a href="{{ route('payroll.dashboard') }}">{{ __('Payroll') }}</a>
            <span style="color:var(--faint)">&rsaquo;</span>
            <span class="here">{{ __('Employees') }}</span>
        </nav>

        {{-- Page head --}}
        <div class="pr-page-head">
            <div>
                <h1>{{ __('Employees') }}</h1>
                <div class="sub">{{ __('All employee records with salary, status and payment method.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('payroll.employees.create') }}" class="pr-btn pr-btn-cta pr-btn-sm">+ {{ __('Add Employee') }}</a>
            </div>
        </div>

        {{-- Status filter boxes --}}
        <div class="pr-card" style="margin-bottom:16px">
            <div class="pr-pad" style="padding-bottom:0">
                <div class="pr-statgrid">
                    <a href="{{ route('payroll.employees.index', array_filter(['search' => request('search'), 'department' => request('department'), 'branch_id' => request('branch_id')])) }}" class="pr-fbox {{ empty(request('status')) ? 'on' : '' }}">
                        <span class="pr-fbox-t pr-t-ink">&#128101;</span>
                        <span><span class="l">{{ __('All') }}</span><span class="v" style="display:block">{{ $totalAll }}</span></span>
                    </a>
                    <a href="{{ route('payroll.employees.index', array_filter(['status' => 'active', 'search' => request('search'), 'department' => request('department'), 'branch_id' => request('branch_id')])) }}" class="pr-fbox {{ request('status') === 'active' ? 'on' : '' }}">
                        <span class="pr-fbox-t pr-t-mint">&#10003;</span>
                        <span><span class="l">{{ __('Active') }}</span><span class="v" style="display:block">{{ $activeCount }}</span></span>
                    </a>
                    <a href="{{ route('payroll.employees.index', array_filter(['status' => 'on_leave', 'search' => request('search'), 'department' => request('department'), 'branch_id' => request('branch_id')])) }}" class="pr-fbox {{ request('status') === 'on_leave' ? 'on' : '' }}">
                        <span class="pr-fbox-t pr-t-amber">&#127796;</span>
                        <span><span class="l">{{ __('On Leave') }}</span><span class="v" style="display:block">{{ $onLeaveCount }}</span></span>
                    </a>
                    <a href="{{ route('payroll.employees.index', array_filter(['status' => 'contract', 'search' => request('search'), 'department' => request('department'), 'branch_id' => request('branch_id')])) }}" class="pr-fbox {{ request('status') === 'contract' ? 'on' : '' }}">
                        <span class="pr-fbox-t pr-t-steel">&#128196;</span>
                        <span><span class="l">{{ __('Contract') }}</span><span class="v" style="display:block">{{ $contractCount }}</span></span>
                    </a>
                    <a href="{{ route('payroll.employees.index', array_filter(['status' => 'terminated', 'search' => request('search'), 'department' => request('department'), 'branch_id' => request('branch_id')])) }}" class="pr-fbox {{ request('status') === 'terminated' ? 'on' : '' }}">
                        <span class="pr-fbox-t pr-t-red">&#10007;</span>
                        <span><span class="l">{{ __('Terminated') }}</span><span class="v" style="display:block">{{ $terminatedCount }}</span></span>
                    </a>
                </div>

                {{-- Controls --}}
                <div class="pr-controls">
                    <form method="GET" action="{{ route('payroll.employees.index') }}" id="pr-filter-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;width:100%">
                        <div class="pr-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" class="pr-input" placeholder="{{ __('Employee No, name, email...') }}" value="{{ request('search') }}">
                        </div>
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <select name="department" class="pr-input" onchange="this.form.submit()">
                            <option value="">{{ __('All Departments') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                        <select name="branch_id" class="pr-input" onchange="this.form.submit()">
                            <option value="">{{ __('All Branches') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if(request()->hasAny(['search', 'department', 'branch_id', 'status']))
                            <a href="{{ route('payroll.employees.index') }}" class="pr-btn pr-btn-ghost pr-btn-sm">{{ __('Clear') }}</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Employees Table --}}
        <div class="pr-card">
            <div class="pr-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Emp No') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Department') }}</th>
                            <th>{{ __('Position') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Joined') }}</th>
                            <th class="num">{{ __('Basic') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td class="pr-mono">{{ $emp->employee_number }}</td>
                                <td style="font-weight:700;color:var(--ink)">{{ $emp->first_name }} {{ $emp->last_name }}</td>
                                <td class="pr-em">{{ $emp->department ?? '—' }}</td>
                                <td class="pr-em">{{ $emp->position ?? '—' }}</td>
                                <td>
                                    @php
                                        $typeMap = [
                                            'full_time'  => ['label' => __('Permanent'), 'chip' => ''],
                                            'part_time'  => ['label' => __('Part Time'), 'chip' => ' pr-tchip-amber'],
                                            'contract'   => ['label' => __('Contract'), 'chip' => ' pr-tchip-steel'],
                                            'intern'     => ['label' => __('Intern'), 'chip' => ' pr-tchip-green'],
                                        ];
                                        $typeInfo = $typeMap[$emp->employment_type ?? 'full_time'] ?? ['label' => $emp->employment_type ?? '—', 'chip' => ''];
                                    @endphp
                                    <span class="pr-tchip{{ $typeInfo['chip'] }}">{{ $typeInfo['label'] }}</span>
                                </td>
                                <td class="pr-em">{{ $emp->hire_date?->format('d M Y') ?? '—' }}</td>
                                <td class="pr-numr bold">{{ format_number($emp->currentSalaryStructure?->basic_salary ?? 0) }}</td>
                                <td><x-payroll::badge :status="$emp->status ?? 'active'" type="employee" /></td>
                                <td>
                                    <div class="pr-row-act">
                                        <a href="{{ route('payroll.employees.show', $emp) }}" class="pr-ibtn" title="{{ __('View') }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <a href="{{ route('payroll.employees.edit', $emp) }}" class="pr-ibtn" title="{{ __('Edit') }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="pr-em" style="text-align:center;padding:40px">
                                    {{ __('No employees found.') }}
                                    <div style="margin-top:12px">
                                        <a href="{{ route('payroll.employees.create') }}" class="pr-btn pr-btn-cta pr-btn-sm">+ {{ __('Add your first employee') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($employees->hasPages())
                <div class="pr-pagi">
                    <span class="t">{{ __('Showing') }} {{ $employees->firstItem() }}&ndash;{{ $employees->lastItem() }} {{ __('of') }} {{ $employees->total() }} {{ __('employees') }}</span>
                    <div>{{ $employees->links() }}</div>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
