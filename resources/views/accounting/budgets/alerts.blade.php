<x-app-layout>
    <div class="bu-wrap max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="page-head">
            <div>
                <h1 style="font-size:21px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">Budget Alerts</h1>
                <div class="sub">Threshold alerts, unusual-spend detection, and low-balance warnings.</div>
            </div>
        </div>

        <div class="bu-g3" style="margin-top:20px">
            <div class="bu-card" style="grid-column:1/-1">
                <div class="bu-card-h">Alert Rules</div>
                <div class="bu-pad">
                    <form method="POST" action="{{ route('accounting.budgets.alert-rules.store') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        @csrf
                        <div class="bu-f">
                            <label>Rule Name <span style="color:var(--red-2)">*</span></label>
                            <input type="text" name="name" class="in" required placeholder="e.g. High Spend Alert">
                        </div>
                        <div class="bu-f">
                            <label>Rule Type</label>
                            <select name="rule_type" class="in">
                                <option value="threshold">Threshold %</option>
                                <option value="unusual">Unusual Spend</option>
                                <option value="low_balance">Low Balance</option>
                            </select>
                        </div>
                        <div class="bu-f">
                            <label>Warn Threshold %</label>
                            <input type="number" name="warn_threshold" class="in" value="85" min="1" max="100">
                        </div>
                        <div class="bu-f">
                            <label>Exceed Threshold %</label>
                            <input type="number" name="exceed_threshold" class="in" value="100" min="1" max="100">
                        </div>
                        <div class="bu-f">
                            <label>Scope</label>
                            <select name="scope" class="in">
                                <option value="budget">Budget-Wide</option>
                                <option value="department">Department</option>
                                <option value="line">Line Item</option>
                            </select>
                        </div>
                        <button type="submit" class="bu-btn bu-btn-cta bu-btn-sm">Create Rule</button>
                    </form>

                    @if($alertRules->count())
                        <div class="bu-li-wrap" style="margin-top:12px">
                            <table>
                                <thead><tr><th>Name</th><th>Type</th><th class="num">Warn %</th><th class="num">Exceed %</th><th>Scope</th><th>Active</th></tr></thead>
                                <tbody>
                                    @foreach($alertRules as $rule)
                                        <tr>
                                            <td style="font-weight:700;color:var(--ink)">{{ $rule->name ?? '—' }}</td>
                                            <td><span class="bu-badge bu-b-app">{{ $rule->typeLabel() }}</span></td>
                                            <td class="num">{{ $rule->warn_threshold ?? '—' }}%</td>
                                            <td class="num">{{ $rule->exceed_threshold ?? '—' }}%</td>
                                            <td>{{ $rule->scopeLabel() }}</td>
                                            <td><span class="bu-badge bu-b-{{ $rule->is_active ? 'app' : 'draft' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bu-card" style="margin-top:16px">
            <div class="bu-card-h">Recent Alerts</div>
            <div class="bu-pad">
                <div class="bu-li-wrap">
                    <table>
                        <thead><tr><th>Alert</th><th>Budget</th><th>Severity</th><th>Fired</th><th></th></tr></thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td style="font-size:12.5px;color:var(--ink)">{{ $alert->message }}</td>
                                    <td>{{ $alert->budget?->name ?? '—' }}</td>
                                    <td><span class="bu-badge bu-b-{{ $alert->severity === 'exceeded' ? 'lock' : ($alert->severity === 'nearing' ? 'pend' : 'app') }}">{{ $alert->severityLabel() }}</span></td>
                                    <td style="font-size:12px;color:var(--muted)">{{ $alert->created_at->diffForHumans() }}</td>
                                    <td>
                                        @unless($alert->is_read)
                                            <form method="POST" action="{{ route('accounting.budgets.alerts.read', $alert) }}" style="display:inline">
                                                @csrf
                                                <button type="submit" class="bu-btn bu-btn-ghost bu-btn-sm">Mark Read</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="bu-empty">No alerts. All clear!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px">{{ $alerts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
