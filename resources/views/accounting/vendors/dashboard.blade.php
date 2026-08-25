@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $currentSort = request()->query('top_sort', 'spend');
@endphp
<x-app-layout>

<style>
:root{--deep-2:#0c3539;--sec:#128F8E;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--hair:#EEF3F1;--bg:#eef4f4;--green:#15803d;--red:#b91c1c;--amber:#b45309;
  --shadow:0 1px 2px rgba(10,42,46,.04),0 10px 26px -14px rgba(10,42,46,.14);}
.wrap{max-width:1200px;margin:0 auto;padding:28px 28px 60px}
.head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.head h1{font-size:22px;font-weight:850;color:var(--ink)}
.head .sub{font-size:12px;color:var(--muted);margin-top:3px}
.head .grow{flex:1}
.search{height:40px;width:260px;border-radius:11px;border:1px solid var(--border);padding:0 14px 0 36px;font-family:inherit;font-size:12.5px;color:var(--ink);background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%235f7476' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cline x1='21' y1='21' x2='16' y2='16'/%3E%3C/svg%3E") no-repeat 12px center}
.search:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.12)}
.btn{display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 16px;border-radius:11px;border:1px solid transparent;cursor:pointer;font-family:inherit;font-weight:700;font-size:12.5px;white-space:nowrap;text-decoration:none}
.btn-cta{color:#fff;background:var(--deep-2)}
.btn-ghost{color:var(--sub);background:#fff;border-color:var(--border)}
.btn-ghost:hover{color:var(--sec);border-color:rgba(18,143,142,.5)}
.more{position:relative}
.menu{position:absolute;right:0;top:46px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:6px;display:none;min-width:170px;z-index:40}
.menu.on{display:block}
.menu a{display:block;width:100%;text-align:left;border:none;background:none;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:12.5px;font-weight:600;color:var(--sub);cursor:pointer;text-decoration:none}
.menu a:hover{background:var(--hair);color:var(--ink)}
.stats{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);display:grid;grid-template-columns:repeat(5,1fr);margin-bottom:20px;overflow:hidden}
@media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr)}}
.stat{padding:16px 20px;border-left:1px solid var(--line);text-decoration:none;display:block}
.stat:first-child{border-left:none}
.stat .l{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.stat .v{font-size:20px;font-weight:850;color:var(--ink);margin-top:4px;font-variant-numeric:tabular-nums}
.stat .v.red{color:var(--red)}
.stat .n{font-size:10.5px;color:var(--faint);margin-top:2px}
.grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-bottom:20px}
@media(max-width:1000px){.grid{grid-template-columns:1fr}}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px}
.grid .card{margin-bottom:0}
.card-h{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.card-h h2{font-size:13px;font-weight:800;color:var(--ink)}
.card-h a{margin-left:auto;font-size:11.5px;font-weight:700;color:var(--sec);text-decoration:none}
.card-b{padding:16px 20px}
.tabs{display:flex;gap:4px;background:#e8f0f0;padding:4px;border-radius:11px;margin-left:auto}
.tabs a{border:none;background:transparent;height:30px;padding:0 12px;border-radius:8px;font-weight:800;font-size:11px;color:var(--muted);cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.tabs a.on{background:#fff;color:var(--sec);box-shadow:0 1px 2px rgba(8,40,44,.1)}
.age{display:flex;flex-direction:column;gap:10px}
.age .r{display:grid;grid-template-columns:80px 1fr 100px;gap:12px;align-items:center}
.age .lab{font-size:11.5px;font-weight:700;color:var(--sub)}
.age .bar{height:8px;border-radius:4px;background:var(--hair);overflow:hidden}
.age .bar i{display:block;height:100%;border-radius:4px;font-style:normal}
.age .amt{font-size:12px;font-weight:700;color:var(--ink);text-align:right;font-variant-numeric:tabular-nums}
.due{display:flex;flex-direction:column}
.due .i{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--hair)}
.due .i:last-child{border-bottom:none}
.due .dot{width:8px;height:8px;border-radius:50%;flex:none}
.due .t{font-size:12.5px;font-weight:700;color:var(--ink)}
.due .s{font-size:11px;color:var(--muted)}
.due .amt{margin-left:auto;font-size:12.5px;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
table{width:100%;border-collapse:collapse}
th{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);text-align:left;padding:8px 20px;border-bottom:1px solid var(--line)}
th.num,td.num{text-align:right}
td{padding:11px 20px;border-bottom:1px solid var(--hair);font-size:12.5px;color:var(--sub)}
tr:last-child td{border-bottom:none}
td.num{font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)}
.name{font-weight:700;color:var(--ink);text-decoration:none}
.name:hover{color:var(--sec)}
.neg{color:var(--red)}
.att{display:flex;flex-direction:column}
.att .i{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--hair);font-size:12.5px;color:var(--sub)}
.att .i:last-child{border-bottom:none}
.att .dot{width:8px;height:8px;border-radius:50%;flex:none}
.att .n{margin-left:auto;font-size:12px;font-weight:800;color:var(--ink)}
</style>

<div class="wrap">

  {{-- header + single-line toolbar --}}
  <div class="head">
    <div><h1>Vendors</h1><div class="sub">Accounts payable overview</div></div>
    <div class="grow"></div>
    <input class="search" placeholder="Search vendors…" onfocus="document.querySelector('.global-search-input')?.focus()">
    <a href="{{ route('accounting.vendors.create') }}" class="btn btn-cta">＋ New Vendor</a>
    <a href="{{ route('accounting.purchase-orders.create') }}" class="btn btn-ghost">Purchase Order</a>
    <a href="{{ route('accounting.bills.create') }}" class="btn btn-ghost">Invoice</a>
    <a href="{{ route('accounting.vendor-payments.create') }}" class="btn btn-ghost">Payment</a>
    <a href="{{ route('accounting.vendor-credits.create') }}" class="btn btn-ghost">Credit Note</a>
    <div class="more" x-data="{ open: false }" @click.outside="open = false">
      <button class="btn btn-ghost" @click.prevent="open = !open">More ⌄</button>
      <div class="menu" :class="{ 'on': open }">
        <a href="{{ route('accounting.vendors.export', $exportParams) }}">Import Vendors</a>
        <a href="{{ route('accounting.vendors.export', $exportParams) }}">Export Vendors</a>
        <a href="{{ route('accounting.vendors.reports') }}">Vendor Statement</a>
        <a href="{{ route('accounting.vendors.reports') }}">Aging Report</a>
      </div>
    </div>
  </div>

  {{-- stat strip --}}
  <div class="stats">
    <a href="{{ route('accounting.vendors.index', ['status' => 'active']) }}" class="stat">
      <div class="l">Vendors</div>
      <div class="v">{{ number_format($vendorCount['total']) }}</div>
      <div class="n">{{ $vendorCount['active'] }} active</div>
    </a>
    <a href="{{ route('accounting.vendors.index') }}" class="stat">
      <div class="l">Total Payables</div>
      <div class="v">{{ \App\Services\VendorCentre\VendorCentreService::compactAmount($totalPayables, $cs) }}</div>
      <div class="n">outstanding</div>
    </a>
    <a href="{{ route('accounting.vendors.index', ['status' => 'due_soon']) }}" class="stat">
      <div class="l">Due This Week</div>
      <div class="v">{{ \App\Services\VendorCentre\VendorCentreService::compactAmount($dueThisWeek['total_amount'], $cs) }}</div>
      <div class="n">{{ $dueThisWeek['count'] }} payment{{ $dueThisWeek['count'] !== 1 ? 's' : '' }}</div>
    </a>
    <a href="{{ route('accounting.vendors.index', ['status' => 'overdue']) }}" class="stat">
      <div class="l">Overdue</div>
      <div class="v red">{{ \App\Services\VendorCentre\VendorCentreService::compactAmount($overdueStats['amount'], $cs) }}</div>
      <div class="n">{{ $overdueStats['vendor_count'] }} vendor{{ $overdueStats['vendor_count'] !== 1 ? 's' : '' }}</div>
    </a>
    <a href="{{ route('accounting.vendors.reports') }}" class="stat">
      <div class="l">Purchases YTD</div>
      <div class="v">{{ \App\Services\VendorCentre\VendorCentreService::compactAmount($purchasesYTD['ytd'], $cs) }}</div>
      <div class="n">{{ $purchasesYTD['pct_change'] >= 0 ? '+' : '' }}{{ $purchasesYTD['pct_change'] }}% vs LY</div>
    </a>
  </div>

  {{-- aging + upcoming --}}
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Payables Aging</h2><a href="{{ route('accounting.vendors.reports') }}">Aging report →</a></div>
      <div class="card-b"><div class="age">
        @foreach($agingBars as $bucket)
          <div class="r"><span class="lab">{{ $bucket['label'] }}</span><div class="bar"><i style="width:{{ $bucket['pct'] }}%;background:{{ $bucket['color'] }}"></i></div><span class="amt">{{ \App\Services\VendorCentre\VendorCentreService::compactAmount($bucket['amount'], $cs) }}</span></div>
        @endforeach
      </div></div>
    </div>
    <div class="card">
      <div class="card-h"><h2>Upcoming Payments</h2><a href="{{ route('accounting.vendor-payments.index') }}">Calendar →</a></div>
      <div class="card-b"><div class="due">
        @forelse($upcomingPayments as $payment)
          <div class="i"><span class="dot" style="background:{{ $payment['dot_color'] }}"></span><div><div class="t">{{ $payment['vendor_name'] }}</div><div class="s">{{ $payment['due_label'] }}</div></div><span class="amt">{{ $cs }}{{ number_format($payment['amount'], 0) }}</span></div>
        @empty
          <div style="padding:20px;text-align:center;color:var(--faint);font-size:12.5px">No payments due in the next 30 days.</div>
        @endforelse
      </div></div>
    </div>
  </div>

  {{-- top vendors with tabs --}}
  <div class="card">
    <div class="card-h"><h2>Top Vendors</h2>
      <div class="tabs">
        <a href="?top_sort=spend" class="{{ $currentSort === 'spend' ? 'on' : '' }}">By Purchase Value</a>
        <a href="?top_sort=out" class="{{ $currentSort === 'out' ? 'on' : '' }}">By Outstanding</a>
        <a href="?top_sort=count" class="{{ $currentSort === 'count' ? 'on' : '' }}">By Transaction Count</a>
      </div>
      <a href="{{ route('accounting.vendors.index') }}" style="margin-left:12px">All vendors →</a></div>
    <table><thead><tr><th>Vendor</th><th class="num">Purchases</th><th class="num">Outstanding</th><th class="num">Transactions</th><th>Last Purchase</th></tr></thead>
    <tbody>
      @forelse($topVendors as $vendor)
        <tr>
          <td><a href="{{ route('accounting.vendors.show', $vendor['vendor_id']) }}" class="name">{{ $vendor['vendor_name'] }}</a></td>
          <td class="num">{{ $cs }}{{ number_format($vendor['purchases'], 0) }}</td>
          <td class="num {{ $vendor['outstanding'] >= 10000000 ? 'neg' : '' }}">{{ $cs }}{{ number_format($vendor['outstanding'], 0) }}</td>
          <td class="num">{{ number_format($vendor['transactions']) }}</td>
          <td>{{ $vendor['last_purchase'] ? \Carbon\Carbon::parse($vendor['last_purchase'])->format('d M') : '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--faint);font-size:12.5px">No vendor data available.</td></tr>
      @endforelse
    </tbody></table>
  </div>

  {{-- pending transactions + attention --}}
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Pending Vendor Transactions</h2><a href="{{ route('accounting.purchase-orders.index') }}">Authorizations →</a></div>
      <table><thead><tr><th>Stage</th><th>Status</th><th class="num">Count</th></tr></thead><tbody>
        @foreach($pendingTransactions as $row)
          @if($row['count'] > 0)
            <tr><td class="name">{{ $row['stage'] }}</td><td>{{ $row['status'] }}</td><td class="num">{{ number_format($row['count']) }}</td></tr>
          @endif
        @endforeach
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-h"><h2>Needs Attention</h2><a href="{{ route('accounting.vendors.reports') }}">Alerts →</a></div>
      <div class="card-b"><div class="att">
        <div class="i"><span class="dot" style="background:var(--red)"></span>Overdue balances<span class="n">{{ number_format($alertCounts['overdue_vendors']) }}</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span>Invoices due within 7 days<span class="n">{{ number_format($alertCounts['due_within_7_days']) }}</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span>Awaiting authorization<span class="n">{{ number_format($alertCounts['awaiting_authorization']) }}</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span>Documents expiring soon<span class="n">0</span></div>
      </div></div>
    </div>
  </div>

  {{-- balances + documents --}}
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Vendor Balances</h2><a href="{{ route('accounting.vendors.index') }}">All balances →</a></div>
      <table><thead><tr><th>Vendor</th><th class="num">Opening</th><th class="num">Purchases</th><th class="num">Payments</th><th class="num">Returns</th><th class="num">Closing</th></tr></thead><tbody>
        @forelse($vendorBalances as $bal)
          <tr>
            <td><a href="{{ route('accounting.vendors.show', $bal['vendor_id']) }}" class="name">{{ $bal['vendor_name'] }}</a></td>
            <td class="num">{{ $cs }}{{ number_format($bal['opening'], 0) }}</td>
            <td class="num">{{ $cs }}{{ number_format($bal['purchases'], 0) }}</td>
            <td class="num">{{ $bal['payments'] > 0 ? '(' . $cs . number_format($bal['payments'], 0) . ')' : '0' }}</td>
            <td class="num">{{ $bal['returns'] > 0 ? '(' . $cs . number_format($bal['returns'], 0) . ')' : '0' }}</td>
            <td class="num">{{ $cs }}{{ number_format($bal['closing'], 0) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="padding:20px;text-align:center;color:var(--faint);font-size:12.5px">No vendor balances to display.</td></tr>
        @endforelse
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-h"><h2>Documents Expiring Soon</h2><a href="#">Compliance →</a></div>
      <table><thead><tr><th>Vendor</th><th>Document</th><th>Expiry</th></tr></thead><tbody>
        <tr><td colspan="3" style="padding:20px;text-align:center;color:var(--faint);font-size:12.5px">No documents expiring soon.</td></tr>
      </tbody></table>
    </div>
  </div>

</div>

</x-app-layout>
