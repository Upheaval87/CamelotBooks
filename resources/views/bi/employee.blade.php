<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'employee';
            $title = 'Employee Productivity';
            $subtitle = 'Payroll cost versus revenue and headcount utilisation per branch for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $branches = $data['branches'] ?? [];
            $branchNames = array_column($branches, 'branch_name', 'branch_id');
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Headcount</div><div class="v">{{ number_format((float) ($data['headcount'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Utilised Employees</div><div class="v">{{ number_format((float) ($data['utilised_employees'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Utilisation</div><div class="v">{{ format_number($data['utilisation_pct'] ?? 0, 1) }}%</div></div>
            <div class="an-kpi"><div class="l">Branches</div><div class="v">{{ count($branches) }}</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Branch Utilisation</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="r">Headcount</th>
                            <th class="r">Revenue</th>
                            <th class="r">Payroll</th>
                            <th class="r">Cost / Employee</th>
                            <th class="r">Revenue / Employee</th>
                            <th class="r">Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $row)
                            <tr>
                                <td>{{ $row['branch_name'] ?? '' }}</td>
                                <td class="r">{{ number_format((float) ($row['headcount'] ?? 0), 0) }}</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                                <td class="r">@money($row['total_payroll'] ?? 0)</td>
                                <td class="r">@money($row['cost_per_employee'] ?? 0)</td>
                                <td class="r">@money($row['revenue_per_employee'] ?? 0)</td>
                                <td class="r">{{ isset($row['ratio']) && $row['ratio'] !== null ? format_number($row['ratio'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="c an-empty-cell">No branch utilisation data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Employee Detail</h2>
                    <p class="an-card-s">Utilised marks employees with realised (approved/posted) payroll in the period.</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Branch</th>
                            <th class="r">Gross Compensation</th>
                            <th class="r">Compensation Index</th>
                            <th class="c">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($data['by_employee'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['employee_name'] ?? '' }}</td>
                                <td>{{ $row['position'] ?? '—' }}</td>
                                <td>{{ $row['department'] ?? '—' }}</td>
                                <td>{{ $branchNames[(int) ($row['branch_id'] ?? 0)] ?? 'Unallocated' }}</td>
                                <td class="r">@money($row['gross_compensation'] ?? 0)</td>
                                <td class="r">{{ format_number($row['comp_index'] ?? 0, 0) }}</td>
                                <td class="c"><span class="an-chip {{ ($row['utilised'] ?? false) ? 'c-ok' : 'c-nt' }}">{{ ($row['utilised'] ?? false) ? 'Utilised' : 'On roll' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="c an-empty-cell">No employee detail.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>