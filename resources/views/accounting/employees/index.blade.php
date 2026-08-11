@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $search = request('search');
    $department = request('department');
    $statusFilter = request('status');
    $initialsFor = function ($name) {
        $words = explode(' ', trim((string) $name));
        $ini = '';
        foreach ($words as $w) {
            if (mb_strlen($w) > 0) {
                $ini .= mb_strtoupper(mb_substr($w, 0, 1));
            }
        }
        return mb_substr($ini, 0, 2);
    };
@endphp
<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Employees</h1>
                        <div class="sub">Manage employee records, payroll and compensation details.</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.employees.create') }}" class="btn cta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            Create Employee
                        </a>
                    </div>
                </div>

                <div class="shell">
                    <div>

                        {{-- Portfolio --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                <h2>Portfolio</h2>
                                <span class="rule"></span>
                            </div>

                            <div class="sgrid">
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                    <div>
                                        <div class="l">Employees</div>
                                        <div class="v">{{ number_format($stats['total']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Active</div>
                                        <div class="v">{{ number_format($stats['active']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h-8m0 0l3.5-3.5M9 8l3.5 3.5M7 16h8m0 0l-3.5 3.5M15 16l-3.5-3.5"/></svg></span>
                                    <div>
                                        <div class="l">On Leave</div>
                                        <div class="v">{{ number_format($stats['on_leave']) }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- controls: search + filters --}}
                            <form method="GET" action="{{ route('accounting.employees.index') }}" id="emp-list-form">
                                <div class="controls">
                                    <div class="search">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                                        <input type="text" name="search" class="input" placeholder="Name, number, or email..." value="{{ $search }}" />
                                    </div>
                                    <input type="text" name="department" class="input" placeholder="Department..." value="{{ $department }}" style="width:11rem" />
                                    <select name="status" class="input" style="width:9rem">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <button type="submit" class="btn ghost">Filter</button>
                                    @if(request()->hasAny('search', 'department', 'status'))
                                        <a href="{{ route('accounting.employees.index') }}" class="btn ghost">Clear</a>
                                    @endif
                                    <span class="chip-t">{{ $employees->total() }} employees</span>
                                </div>
                            </form>
                        </section>

                        {{-- employee list --}}
                        <section class="card" style="padding:20px 24px; margin-top:16px">
                            <div class="li-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:13%">Employee #</th>
                                            <th style="width:22%">Name</th>
                                            <th style="width:20%">Position</th>
                                            <th style="width:15%">Department</th>
                                            <th style="width:14%">Branch</th>
                                            <th style="width:10%">Status</th>
                                            <th style="width:8%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($employees as $employee)
                                        <tr>
                                            <td class="mono">{{ $employee->employee_number }}</td>
                                            <td>
                                                <div class="cust">
                                                    <span class="ava">{{ $initialsFor($employee->full_name) }}</span>
                                                    <div class="nm">
                                                        <a href="{{ route('accounting.employees.show', $employee) }}" class="n">{{ $employee->full_name }}</a>
                                                        @if($employee->email)
                                                            <span class="s">{{ $employee->email }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="em">{{ $employee->position ?? '—' }}</td>
                                            <td class="em">{{ $employee->department ?? '—' }}</td>
                                            <td class="em">{{ $employee->branch->name ?? '—' }}</td>
                                            <td>
                                                @if($employee->is_active)
                                                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                                                @else
                                                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.employees.show', $employee) }}" class="ibtn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                                    <a href="{{ route('accounting.employees.edit', $employee) }}" class="ibtn" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                                    <form method="POST" action="{{ route('accounting.employees.toggle', $employee) }}" class="inline" @if($employee->is_active) onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this employee?') }}', { type: 'danger' })" @endif>
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="ibtn {{ $employee->is_active ? 'del' : '' }}" title="{{ $employee->is_active ? 'Deactivate' : 'Activate' }}">
                                                            @if($employee->is_active)
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16m-10 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7"/></svg>
                                                            @else
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7"><div class="empty">No employees found.</div></td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                @if($employees->hasPages())
                                    @php
                                        $paginator = $employees->appends(request()->query());
                                        $last = $paginator->lastPage();
                                        $cur = $paginator->currentPage();
                                        $winStart = max(1, $cur - 2);
                                        $winEnd = min($last, $cur + 2);
                                        $firstItem = $paginator->firstItem() ?: 0;
                                        $lastItem = $paginator->lastItem() ?: 0;
                                    @endphp
                                    <div class="pagi">
                                        <span class="t">Showing {{ $firstItem }}–{{ $lastItem }} of {{ $paginator->total() }} employees</span>
                                        <span class="pg">
                                            @if($paginator->onFirstPage())
                                                <span class="pgbtn" aria-disabled="true" aria-label="Previous">‹</span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">‹</a>
                                            @endif

                                            @if($winStart > 1)
                                                <a href="{{ $paginator->url(1) }}">1</a>
                                                @if($winStart > 2)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                            @endif

                                            @for($page = $winStart; $page <= $winEnd; $page++)
                                                @if($page === $cur)
                                                    <span class="pgbtn cur" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($winEnd < $last)
                                                @if($winEnd < $last - 1)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                                <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
                                            @endif

                                            @if($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next">›</a>
                                            @else
                                                <span class="pgbtn" aria-disabled="true" aria-label="Next">›</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    {{-- right rail --}}
                    <aside class="railsum">
                        <div class="card">
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                    <h2>Views</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.employees.index') }}" class="vitem {{ !$search && !$department && !$statusFilter ? 'on' : '' }}" {{ !$search && !$department && !$statusFilter ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                        All Employees
                                    </a>
                                    <a href="{{ route('accounting.employees.index', ['status' => 'active']) }}" class="vitem {{ $statusFilter === 'active' ? 'on' : '' }}" {{ $statusFilter === 'active' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Active
                                    </a>
                                    <a href="{{ route('accounting.employees.index', ['status' => 'inactive']) }}" class="vitem {{ $statusFilter === 'inactive' ? 'on' : '' }}" {{ $statusFilter === 'inactive' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Inactive
                                    </a>
                                    <a href="{{ route('accounting.employees.create') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg></span>
                                        Create Employee
                                    </a>
                                </div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                    <h2>Reports</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.reports.payroll-register') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-2 0-4 .8-4 2s2 2 4 2 4-.8 4-2-2-2-4-2zm0 0V4m-4 6c0 1.2 1.8 2 4 2s4-.8 4-2m-8 0v6c0 1.2 1.8 2 4 2s4-.8 4-2v-6"/></svg></span>
                                        Payroll Register
                                    </a>
                                    <a href="{{ route('accounting.reports.payroll-summary') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                                        Payroll Summary
                                    </a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
