<x-app-layout>
    <div class="pos">
        <div class="wrap">
            {{-- Page Head --}}
            <div class="pos-page-head">
                <div>
                    <h1>Register &amp; Shift</h1>
                    <div class="pos-sub">Open / close till · cash count · shift history</div>
                </div>
            </div>

            {{-- KPIs --}}
            @php
                $openCount = $sessions->filter(fn($s) => $s->isOpen())->count();
                $closedCount = $sessions->filter(fn($s) => $s->isClosed())->count();
            @endphp
            <div class="pos-kpis" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Open Tills</div>
                    <div class="pos-kpi-v">{{ $openCount }}</div>
                    <div class="pos-kpi-n" style="color:#dff7f6">{{ $terminals->count() }} terminals total</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Total Sessions</div>
                    <div class="pos-kpi-v">{{ $sessions->total() }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Closed Today</div>
                    <div class="pos-kpi-v">{{ $closedCount }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Terminals Active</div>
                    <div class="pos-kpi-v">{{ $terminals->count() }}</div>
                </div>
            </div>

            {{-- Open Till Form --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">1 · Open New Till</span>
                </div>
                <div class="pos-pad">
                    <form method="POST" action="{{ route('pos.register.open') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                        @csrf
                        <div class="pos-f" style="flex:1;min-width:200px">
                            <label>Terminal</label>
                            <select name="terminal_id" class="pos-in" required>
                                <option value="">— Select Terminal —</option>
                                @foreach($terminals as $terminal)
                                    <option value="{{ $terminal->id }}" {{ old('terminal_id') == $terminal->id ? 'selected' : '' }}>
                                        {{ $terminal->identifier }} – {{ $terminal->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pos-f" style="width:180px">
                            <label>Opening Float</label>
                            <input type="number" name="opening_float" class="pos-in" step="0.01" min="0" value="{{ old('opening_float', '0.00') }}" required>
                        </div>
                        <button type="submit" class="pos-btn pos-btn-cta">Open Till</button>
                    </form>
                </div>
            </div>

            {{-- Sessions Table + Rail --}}
            <div class="pos-shell">
                <div class="pos-card">
                    <div class="pos-card-h">
                        <h2>Shift History</h2>
                    </div>
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Terminal</th>
                                <th>Cashier</th>
                                <th class="num">Float</th>
                                <th class="num">Expected</th>
                                <th class="num">Actual</th>
                                <th class="num">Variance</th>
                                <th>Status</th>
                                <th>Opened</th>
                                <th>Closed</th>
                                <th class="num">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="pos-mono pos-em">{{ $session->id }}</td>
                                    <td class="pos-bold">{{ $session->terminal?->identifier ?? '—' }}</td>
                                    <td class="pos-em">{{ $session->user?->name ?? '—' }}</td>
                                    <td class="num">{{ format_money($session->opening_float) }}</td>
                                    <td class="num">{{ $session->expected_cash !== null ? format_money($session->expected_cash) : '—' }}</td>
                                    <td class="num">{{ $session->actual_cash_count !== null ? format_money($session->actual_cash_count) : '—' }}</td>
                                    <td class="num @if($session->variance > 0) style="color:var(--pos-green)" @elseif($session->variance < 0) style="color:var(--pos-red)" @endif">
                                        @if($session->variance !== null)
                                            {{ $session->variance >= 0 ? '+' : '' }}{{ format_money($session->variance) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($session->isOpen())
                                            <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Open</span>
                                        @else
                                            <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Closed</span>
                                        @endif
                                    </td>
                                    <td class="pos-em">{{ $session->opened_at?->format('d M H:i') ?? '—' }}</td>
                                    <td class="pos-em">{{ $session->closed_at?->format('d M H:i') ?? '—' }}</td>
                                    <td class="num">
                                        <div class="pos-row-act">
                                            @if($session->isOpen())
                                                <button type="button" onclick="document.getElementById('close-modal-{{ $session->id }}').classList.remove('hidden')" class="pos-btn pos-btn-danger-o pos-btn-xs">Close Till</button>
                                            @endif
                                            @if($session->isClosed())
                                                <a href="{{ route('pos.register.show', $session) }}" class="pos-btn pos-btn-ghost pos-btn-xs">View</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Close Till Modal --}}
                                @if($session->isOpen())
                                    <div id="close-modal-{{ $session->id }}" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                                        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Close Till – Session #{{ $session->id }}</h3>
                                            <p class="text-sm text-gray-600 mb-4">Terminal: {{ $session->terminal?->identifier }} | Float: {{ format_money($session->opening_float) }}</p>
                                            <form method="POST" action="{{ route('pos.register.close', $session) }}">
                                                @csrf
                                                <div class="mb-4">
                                                    <label style="display:block;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--pos-muted);margin-bottom:7px">Actual Cash Count</label>
                                                    <input type="number" name="actual_cash_count" class="pos-in" step="0.01" min="0" value="{{ old('actual_cash_count', '0.00') }}" required placeholder="Count the cash in the drawer">
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" onclick="document.getElementById('close-modal-{{ $session->id }}').classList.add('hidden')" class="pos-btn pos-btn-ghost pos-btn-sm">Cancel</button>
                                                    <button type="submit" class="pos-btn pos-btn-cta">Close Till</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="11">
                                        <div class="pos-empty">
                                            <h3>No till sessions yet</h3>
                                            <p>Open a till above to start processing POS sales.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pos-pag">
                    <span>Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }} sessions</span>
                    {{ $sessions->links() }}
                </div>
            </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.reports.x-report') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            X-Report
                        </a>
                        <a href="{{ route('pos.reports.z-report') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Z-Report
                        </a>
                        <a href="{{ route('pos.receipts.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Receipts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
