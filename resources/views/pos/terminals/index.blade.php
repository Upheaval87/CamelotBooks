<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Registers</h1>
                <p class="pos-sub">Terminals · shifts · cashiers &amp; permissions</p>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                {{-- Section 1: Add Terminal --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">1 · Add Terminal</span>
                    </div>
                    <div class="pos-pad">
                        <form method="POST" action="{{ route('pos.terminals.store') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                            @csrf
                            <div class="pos-f" style="flex:1;min-width:150px">
                                <label>Identifier <span style="color:var(--pos-red)">*</span></label>
                                <input class="pos-in" name="identifier" value="{{ old('identifier') }}" required placeholder="e.g. T1, REGISTER-01">
                                @error('identifier')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                            </div>
                            <div class="pos-f" style="flex:1;min-width:200px">
                                <label>Name <span style="color:var(--pos-red)">*</span></label>
                                <input class="pos-in" name="name" value="{{ old('name') }}" required placeholder="e.g. Front Counter">
                                @error('name')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                            </div>
                            <div class="pos-f" style="flex:1;min-width:180px">
                                <label>Branch</label>
                                <x-scoped-search-field
                                    name="branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="old('branch_id')"
                                    :label="old('branch_id') ? (($branches->firstWhere('id', (int) old('branch_id'))?->code ?? '') . ' - ' . ($branches->firstWhere('id', (int) old('branch_id'))?->name ?? '')) : ''"
                                    placeholder="{{ __('No branch') }}"
                                />
                            </div>
                            <div class="pos-f" style="width:180px">
                                <label>PIN Timeout (min)</label>
                                <input type="number" class="pos-in" name="cashier_pin_timeout_minutes" value="{{ old('cashier_pin_timeout_minutes', 0) }}" min="0" max="480">
                                @error('cashier_pin_timeout_minutes')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="pos-btn pos-btn-cta">Add</button>
                        </form>
                    </div>
                </div>

                {{-- Section 2: Registers/Terminals Table --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">2 · Registers / Terminals</span>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Identifier</th>
                                    <th>Name</th>
                                    <th>Branch</th>
                                    <th class="num">PIN Timeout</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($terminals as $terminal)
                                    <tr>
                                        <td class="pos-mono pos-bold">{{ $terminal->identifier }}</td>
                                        <td>{{ $terminal->name }}</td>
                                        <td class="pos-em">{{ $terminal->branch?->name ?? '—' }}</td>
                                        <td class="num">{{ $terminal->cashier_pin_timeout_minutes > 0 ? $terminal->cashier_pin_timeout_minutes . ' min' : 'Disabled' }}</td>
                                        <td>
                                            @if($terminal->is_active)
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                            @else
                                                <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <form method="POST" action="{{ route('pos.terminals.toggle', $terminal) }}" style="display:inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="pos-btn pos-btn-xs {{ $terminal->is_active ? 'pos-btn-danger-o' : 'pos-btn-sec' }}">
                                                        {{ $terminal->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="pos-empty">
                                                <h3>No terminals found</h3>
                                                <p>Add a terminal to start processing POS sales.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Section 3: Recent Shifts --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">3 · Recent Shifts</span>
                        <div class="pos-right">
                            <a href="{{ route('pos.register.index') }}" style="font-size:12px;font-weight:700;color:var(--pos-sec);text-decoration:none">View all →</a>
                        </div>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Terminal</th>
                                    <th>Cashier</th>
                                    <th>Opened</th>
                                    <th class="num">Float</th>
                                    <th class="num">Expected</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentSessions = \App\Models\PosCashierSession::forCompany((int) session('current_company_id'))
                                        ->with(['terminal:id,identifier', 'user:id,name'])
                                        ->latest('opened_at')
                                        ->limit(5)
                                        ->get();
                                @endphp
                                @forelse($recentSessions as $session)
                                    <tr>
                                        <td class="pos-bold">{{ $session->terminal?->identifier ?? '—' }}</td>
                                        <td>{{ $session->user?->name ?? '—' }}</td>
                                        <td class="pos-em">{{ $session->opened_at?->format('d M H:i') ?? '—' }}</td>
                                        <td class="num">{{ format_money($session->opening_float) }}</td>
                                        <td class="num">{{ $session->expected_cash !== null ? format_money($session->expected_cash) : '—' }}</td>
                                        <td>
                                            @if($session->isOpen())
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Open</span>
                                            @else
                                                <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Closed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="pos-empty" style="padding:24px 12px">
                                                <h3>No shifts yet</h3>
                                                <p>Open a till to start tracking shifts.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Section 4: Cashiers & Permissions --}}
                <div class="pos-card">
                    <div class="pos-card-h">
                        <span class="pos-step">4 · Cashiers &amp; Permissions</span>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Cashier</th>
                                    <th class="num">PIN Set</th>
                                    <th class="num">Can Discount</th>
                                    <th class="num">Can Void</th>
                                    <th class="num">Can Refund</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $cashierUsers = \App\Models\User::where('company_id', (int) session('current_company_id'))
                                        ->where('pos_pin_hash', '!=', null)
                                        ->get(['id', 'name', 'pos_pin_hash']);
                                @endphp
                                @forelse($cashierUsers as $cashier)
                                    <tr>
                                        <td class="pos-bold">{{ $cashier->name }}</td>
                                        <td class="num">
                                            @if($cashier->hasPosPin())
                                                <span style="color:var(--pos-green)">✓ Yes</span>
                                            @else
                                                <span class="pos-em">No</span>
                                            @endif
                                        </td>
                                        <td class="num"><span class="pos-em">Display only</span></td>
                                        <td class="num"><span class="pos-em">Display only</span></td>
                                        <td class="num"><span class="pos-em">Display only</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="pos-empty" style="padding:24px 12px">
                                                <h3>No cashiers configured</h3>
                                                <p>Cashiers with PINs will appear here.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.register.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Shifts
                    </a>
                    <a href="{{ route('pos.reports.x-report') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 010-4h14v4"/><path d="M3 5v14a2 2 0 002 2h16v-5"/><path d="M18 12a2 2 0 000 4h4v-4h-4z"/></svg>
                        X-Report
                    </a>
                    <a href="{{ route('pos.reports.z-report') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                        Z-Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
