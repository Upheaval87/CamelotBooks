<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'truecost';
            $title = 'True Total Cost';
            $subtitle = 'All-in landed and logistics cost across inbound, delivery, manufacturing and distribution for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $rows = $data['branches'] ?? [];
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Cost</div><div class="v">@money($data['grand_total'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Branches</div><div class="v">{{ count($rows) }}</div></div>
            <div class="an-kpi"><div class="l">Delivery & Inbound</div><div class="v">@money(array_sum(array_column($rows, 'total_delivery')) + array_sum(array_column($rows, 'total_inbound')))</div></div>
            <div class="an-kpi"><div class="l">Production</div><div class="v">@money(array_sum(array_column($rows, 'labour_related')) + array_sum(array_column($rows, 'machine_related')) + array_sum(array_column($rows, 'finished_goods')))</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Cost Breakdown by Branch</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="r">Inbound Freight</th>
                            <th class="r">Delivery</th>
                            <th class="r">Finished Goods</th>
                            <th class="r">Labour</th>
                            <th class="r">Machine</th>
                            <th class="r">Building & Energy</th>
                            <th class="r">Receiving</th>
                            <th class="r">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['branch_name'] ?? '' }}</td>
                                <td class="r">@money($row['total_inbound'] ?? 0)</td>
                                <td class="r">@money($row['total_delivery'] ?? 0)</td>
                                <td class="r">@money($row['finished_goods'] ?? 0)</td>
                                <td class="r">@money($row['labour_related'] ?? 0)</td>
                                <td class="r">@money($row['machine_related'] ?? 0)</td>
                                <td class="r">@money($row['building_and_energy'] ?? 0)</td>
                                <td class="r">@money($row['receiving'] ?? 0)</td>
                                <td class="r">@money($row['total_cost'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="c an-empty-cell">No cost data.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            @foreach (['total_inbound', 'total_delivery', 'finished_goods', 'labour_related', 'machine_related', 'building_and_energy', 'receiving', 'total_cost'] as $col)
                                <th class="r">@money(array_sum(array_column($rows, $col)))</th>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <p class="an-note">Costs are attributed from supplier invoices, payroll, expenses, fixed-asset depreciation and inventory adjustments mapped to the true-cost accounts set.</p>
    </div>
</x-app-layout>