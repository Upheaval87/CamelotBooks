<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>POS Reports</h1>
                    <div class="pos-sub">Sales · terminals · cashiers · payments · performance</div>
                </div>
            </div>

            <div class="pos-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
                @php
                    $reports = [
                        ['title' => 'X Report', 'desc' => 'End-of-shift sales summary by terminal', 'icon' => '📊', 'route' => 'pos.reports.x-report'],
                        ['title' => 'Z Report', 'desc' => 'End-of-day consolidated sales report', 'icon' => '📈', 'route' => 'pos.reports.z-report'],
                        ['title' => 'Sales by Terminal', 'desc' => 'Sales performance broken down by terminal', 'icon' => '🖥️', 'route' => 'pos.reports.sales-by-terminal'],
                        ['title' => 'Sales by Cashier', 'desc' => 'Individual cashier performance metrics', 'icon' => '👤', 'route' => 'pos.reports.sales-by-cashier'],
                        ['title' => 'Daily Summary', 'desc' => 'Daily revenue, transactions, and averages', 'icon' => '📅', 'route' => 'pos.reports.x-report'],
                        ['title' => 'Payment Breakdown', 'desc' => 'Sales split by payment method', 'icon' => '💳', 'route' => 'pos.reports.sales-by-terminal'],
                        ['title' => 'Product Performance', 'desc' => 'Top-selling and slow-moving items', 'icon' => '📦', 'route' => 'pos.products.index'],
                        ['title' => 'Returns & Refunds', 'desc' => 'Return volume and reasons analysis', 'icon' => '🔄', 'route' => 'pos.returns.index'],
                    ];
                @endphp

                @foreach($reports as $report)
                    <a href="{{ route($report['route']) }}" class="pos-card" style="text-decoration:none;color:inherit;display:block;padding:20px;transition:transform .15s,box-shadow .15s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                        <div style="font-size:28px;margin-bottom:10px">{{ $report['icon'] }}</div>
                        <div style="font-size:14px;font-weight:700;color:var(--pos-ink);margin-bottom:4px">{{ $report['title'] }}</div>
                        <div style="font-size:12px;color:var(--pos-muted);line-height:1.4">{{ $report['desc'] }}</div>
                        <div style="margin-top:12px;font-size:11px;font-weight:700;color:var(--pos-sec);text-transform:uppercase;letter-spacing:.06em">View Report →</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
