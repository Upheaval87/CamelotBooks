<x-app-layout>
  <div class="bu-wrap">
    <div class="bu-page-head">
      <div><h1>Budgeting Dashboard</h1><div class="sub">Overview of all budgets, utilization, and recent alerts.</div></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('accounting.budgets.index') }}" class="bu-btn bu-btn-ghost">View All Budgets</a>
        <a href="{{ route('accounting.budgets.settings') }}" class="bu-btn bu-btn-ghost">Settings</a>
        <a href="{{ route('accounting.budgets.create') }}" class="bu-btn bu-btn-cta">＋ New Budget</a>
      </div>
    </div>
    <x-budgeting-subnav active-tab="dashboard" />

    <div class="bu-kpis" style="margin-bottom:16px">
      <div class="bu-kpi"><div class="l">Total Budgets</div><div class="v">{{ $kpis['total_budgets'] }}</div><div class="n">{{ $kpis['approved_budgets'] }} approved</div></div>
      <div class="bu-kpi"><div class="l">Approved</div><div class="v">{{ $kpis['approved_budgets'] }}</div><div class="n">{{ $kpis['total_budgets'] - $kpis['approved_budgets'] }} others</div></div>
      <div class="bu-kpi"><div class="l">Total Budgeted</div><div class="v">K{{ number_format($kpis['total_budgeted'] / 1000000, 1) }}M</div><div class="n">income + expense lines</div></div>
      <div class="bu-kpi"><div class="l">Total Actual</div><div class="v">K{{ number_format($kpis['total_actual'] / 1000000, 1) }}M</div><div class="n">to {{ now()->format('d M Y') }}</div></div>
      <div class="bu-kpi hero"><div class="l">Utilization</div><div class="v">{{ $kpis['utilization'] }}%</div><div class="n">overall spending rate</div></div>
    </div>

    <div class="bu-g2">
      <div class="bu-card">
        <div class="bu-card-h"><h2>Recent Budgets</h2><div style="margin-left:auto"><a href="{{ route('accounting.budgets.index') }}" class="bu-btn bu-btn-ghost bu-btn-sm">View all</a></div></div>
        <div class="bu-li-wrap">
          <table style="min-width:0">
            <thead><tr><th>Budget</th><th>Status</th><th style="width:40%">Utilization</th></tr></thead>
            <tbody>
              @forelse($recentBudgets as $b)
                <tr>
                  <td style="font-weight:700;color:var(--ink)">{{ $b->name }}</td>
                  <td>@php $statusMap = ['draft'=>['b-draft','Draft'],'pending'=>['b-pend','Pending'],'approved'=>['b-app','Approved'],'rejected'=>['b-lock','Rejected'],'locked'=>['b-lock','Locked']]; $sm = $statusMap[$b->status] ?? ['b-draft',$b->status]; @endphp<span class="bu-badge {{ $sm[0] }}"><span class="bu-bdot"></span>{{ $sm[1] }}</span></td>
                  <td>
                    @php $util = $b->actual_total > 0 ? min(round($b->actual_total / max($b->budgeted_total, 1) * 100), 200) : 0; $uc = $util >= 100 ? 'bu-u-crit' : ($util >= 75 ? 'bu-u-warn' : 'bu-u-ok'); @endphp
                    <div class="bu-util"><div class="bu-ubar"><i class="{{ $uc }}" style="width:{{ min($util, 100) }}%"></i></div><span class="p">{{ $util }}%</span></div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="bu-empty">No budgets yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="bu-card">
        <div class="bu-card-h"><h2>Recent Alerts</h2><div style="margin-left:auto">@if($recentAlerts->count())<span class="bu-badge bu-b-pend"><span class="bu-bdot"></span>{{ $recentAlerts->count() }} alerts</span>@endif</div></div>
        <div class="bu-li-wrap">
          <table style="min-width:0">
            <thead><tr><th>Alert</th><th>Budget</th><th>Severity</th></tr></thead>
            <tbody>
              @forelse($recentAlerts as $alert)
                <tr>
                  <td class="bu-em">{{ $alert->message }}</td>
                  <td class="bu-em">{{ $alert->budget->name ?? '—' }}</td>
                  <td><span class="bu-badge {{ $alert->severity === 'critical' ? 'bu-b-lock' : 'bu-b-pend' }}"><span class="bu-bdot"></span>{{ ucfirst($alert->severity) }}</span></td>
                </tr>
              @empty
                <tr><td colspan="3" class="bu-empty">No alerts. All clear!</td></tr>
            @endforelse
          </tbody>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
