@php
    $statusChips = [
        'draft' => 'fa-chip-gray',
        'active' => 'fa-chip-teal',
        'disposed' => 'fa-chip-red',
        'scrapped' => 'fa-chip-red',
        'pending' => 'fa-chip-amber',
    ];
    $depMethods = [
        'straight_line' => 'Straight Line',
        'declining_balance' => 'Declining Balance',
        'sum_of_years' => 'Sum of Years Digits',
        'units_of_production' => 'Units of Production',
    ];
    $canActivate = $asset->isDraft();
    $canDisposal = $asset->isActive();
    $canTransfer = $asset->isActive();
    $canImpair = $asset->isActive();
    $canRevaluate = $asset->isActive();
@endphp

<x-app-layout>
    <div class="fa-wrap">
        {{-- Head --}}
        <div class="fa-head" style="position:sticky;top:var(--topbar-h,106px);z-index:40;background:rgba(245,247,252,.9);backdrop-filter:blur(12px);padding:.75rem 0;margin-bottom:1.5rem;border-bottom:1px solid var(--line,#e2ecec)">
            <div>
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                    <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-.02em;color:var(--ink,#0B2A2D)">{{ $asset->name }}</h1>
                    <span class="fa-chip {{ $statusChips[$asset->status] ?? 'fa-chip-gray' }}">
                        <span class="fa-chip-dot"></span>
                        {{ $asset->status_label }}
                    </span>
                    <span class="fa-chip fa-chip-navy">{{ $asset->asset_code }}</span>
                </div>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.register') }}" class="fa-btn fa-btn-ghost">Back</a>
                @if ($canActivate)
                    <form method="POST" action="{{ route('accounting.fixed-assets.activate', $asset->id) }}" class="inline">@csrf
                        <button type="submit" class="fa-btn fa-btn-primary" onclick="fbConfirmButton(event, 'Activate this asset?', {type:'action'})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>
                            Activate
                        </button>
                    </form>
                @endif
                <a href="{{ route('accounting.fixed-assets.edit', $asset->id) }}" class="fa-btn fa-btn-ghost">Edit</a>
                @if ($asset->isDraft() || $asset->isActive())
                    <form method="POST" action="{{ route('accounting.fixed-assets.destroy', $asset->id) }}" class="inline">@csrf @method('DELETE')
                        <button type="submit" class="fa-btn fa-btn-danger" onclick="fbConfirmSubmit(event, 'Permanently delete this asset?', {type:'danger'})">Delete</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Workflow Steps --}}
        <div class="fa-wf" style="margin-bottom:1.5rem">
            <div class="fa-wf-step {{ $asset->isDraft() ? 'active' : 'done' }}">
                <span>{{ $asset->isDraft() ? '①' : '✓' }}</span> Draft
            </div>
            <span class="fa-wf-arrow">→</span>
            <div class="fa-wf-step {{ $asset->isActive() ? 'active' : ($asset->isDisposed() ? 'done' : '') }}">
                <span>{{ $asset->isActive() ? '②' : ($asset->isDisposed() ? '✓' : '②') }}</span> Active
            </div>
            <span class="fa-wf-arrow">→</span>
            <div class="fa-wf-step {{ $asset->isDisposed() ? 'active' : '' }}">
                <span>{{ $asset->isDisposed() ? '③' : '③' }}</span> Disposed
            </div>
        </div>

        {{-- KPIs --}}
        <div class="fa-kpi-grid">
            <div class="fa-kpi">
                <div class="fa-kpi-label">Acquisition Cost</div>
                <div class="fa-kpi-value">{{ format_number($asset->acquisition_cost) }}</div>
            </div>
            <div class="fa-kpi">
                <div class="fa-kpi-label">Accumulated Depreciation</div>
                <div class="fa-kpi-value">{{ format_number($asset->accumulated_depreciation) }}</div>
            </div>
            <div class="fa-kpi">
                <div class="fa-kpi-label">Net Book Value</div>
                <div class="fa-kpi-value" style="color:{{ $asset->net_book_value > 0 ? 'var(--ink,#0B2A2D)' : '#b91c1c' }}">{{ format_number($asset->net_book_value) }}</div>
            </div>
            <div class="fa-kpi">
                <div class="fa-kpi-label">Depreciation Method</div>
                <div class="fa-kpi-value" style="font-size:1rem">{{ $depMethods[$asset->depreciation_method] ?? $asset->depreciation_method }}</div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="fa-tabs" id="fa-tabs">
            <button class="fa-tab active" data-tab="overview">Overview</button>
            <button class="fa-tab" data-tab="depreciation">Depreciation</button>
            <button class="fa-tab" data-tab="disposal">Disposal</button>
            <button class="fa-tab" data-tab="transfers">Transfers</button>
            <button class="fa-tab" data-tab="maintenance">Maintenance</button>
            <button class="fa-tab" data-tab="components">Components</button>
            <button class="fa-tab" data-tab="history">History</button>
        </div>

        {{-- Panel: Overview --}}
        <div class="fa-panel active" id="tab-overview">
            <div class="fa-card">
                <div class="fa-card-head">
                    <h2>Asset Details</h2>
                    <div class="fa-wf" style="font-size:.75rem">
                        @if ($canDisposal)
                            <a href="{{ route('accounting.fixed-assets.disposals.create', $asset->id) }}" class="fa-btn fa-btn-sm fa-btn-danger">Dispose</a>
                        @endif
                        @if ($canTransfer)
                            <a href="{{ route('accounting.fixed-assets.transfers.create', $asset->id) }}" class="fa-btn fa-btn-sm fa-btn-ghost">Transfer</a>
                        @endif
                        @if ($canImpair)
                            <a href="{{ route('accounting.fixed-assets.impairments.create', $asset->id) }}" class="fa-btn fa-btn-sm fa-btn-ghost">Impair</a>
                        @endif
                        @if ($canRevaluate)
                            <a href="{{ route('accounting.fixed-assets.revaluations.create', $asset->id) }}" class="fa-btn fa-btn-sm fa-btn-ghost">Revalue</a>
                        @endif
                    </div>
                </div>
                <div class="fa-detail-grid">
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Asset Code</span>
                        <span class="fa-detail-value">{{ $asset->asset_code }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Category</span>
                        <span class="fa-detail-value">{{ $asset->category?->name ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Class</span>
                        <span class="fa-detail-value">{{ $asset->faClass?->name ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Serial Number</span>
                        <span class="fa-detail-value mono">{{ $asset->serial_number ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Tag Number</span>
                        <span class="fa-detail-value mono">{{ $asset->tag_number ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Location</span>
                        <span class="fa-detail-value">{{ $asset->location ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Custodian</span>
                        <span class="fa-detail-value">{{ $asset->custodian ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Vendor</span>
                        <span class="fa-detail-value">{{ $asset->vendor?->name ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Branch</span>
                        <span class="fa-detail-value">{{ $asset->branch?->name ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Cost Centre</span>
                        <span class="fa-detail-value">{{ $asset->costCenter?->name ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Acquisition Date</span>
                        <span class="fa-detail-value">{{ $asset->acquisition_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">In-Service Date</span>
                        <span class="fa-detail-value">{{ $asset->in_service_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Useful Life</span>
                        <span class="fa-detail-value">{{ $asset->useful_life }} months</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Residual Value</span>
                        <span class="fa-detail-value">{{ format_number($asset->residual_value) }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Depreciation Rate</span>
                        <span class="fa-detail-value">{{ $asset->depreciation_rate ? number_format($asset->depreciation_rate, 2).'%' : '—' }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Created By</span>
                        <span class="fa-detail-value">{{ $asset->creator?->name ?? '—' }}</span>
                    </div>
                </div>
                @if ($asset->description)
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line,#e2ecec)">
                        <span class="fa-detail-label" style="display:block;margin-bottom:.25rem">Description</span>
                        <span style="font-size:.8125rem;color:var(--ink,#0B2A2D)">{{ $asset->description }}</span>
                    </div>
                @endif
            </div>

            {{-- GL Accounts --}}
            <div class="fa-card">
                <div class="fa-card-head">
                    <h2>GL Accounts</h2>
                </div>
                <div class="fa-detail-grid">
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Asset Account</span>
                        <span class="fa-detail-value mono">{{ $asset->assetAccount?->code }} — {{ $asset->assetAccount?->name }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Accumulated Depreciation</span>
                        <span class="fa-detail-value mono">{{ $asset->accumDepAccount?->code }} — {{ $asset->accumDepAccount?->name }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Depreciation Expense</span>
                        <span class="fa-detail-value mono">{{ $asset->depExpenseAccount?->code }} — {{ $asset->depExpenseAccount?->name }}</span>
                    </div>
                    <div class="fa-detail-field">
                        <span class="fa-detail-label">Disposal Account</span>
                        <span class="fa-detail-value mono">{{ $asset->disposalAccount ? $asset->disposalAccount->code.' — '.$asset->disposalAccount->name : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Recent Disposals --}}
            @if ($asset->disposals->count())
                <div class="fa-card">
                    <div class="fa-card-head"><h2>Disposal Records</h2></div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>Method</th><th>Proceeds</th><th>Gain/Loss</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($asset->disposals as $d)
                                    <tr>
                                        <td>{{ $d->disposal_date?->format('d M Y') }}</td>
                                        <td>{{ ucfirst($d->disposal_method) }}</td>
                                        <td class="numr">{{ format_number($d->proceeds_amount) }}</td>
                                        <td class="numr {{ ($d->gain_loss ?? 0) < 0 ? 'neg' : '' }}">{{ format_number($d->gain_loss ?? 0) }}</td>
                                        <td><span class="fa-chip {{ $d->isApproved() ? 'fa-chip-green' : ($d->isRejected() ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $d->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Panel: Depreciation --}}
        <div class="fa-panel" id="tab-depreciation">
            <div class="fa-card">
                <div class="fa-card-head">
                    <h2>Depreciation Books</h2>
                </div>
                @if ($asset->depBooks->count())
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Book Type</th><th>Period</th><th>Depreciation</th><th>Accumulated</th><th>NBV</th></tr></thead>
                            <tbody>
                                @foreach ($asset->depBooks as $book)
                                    <tr>
                                        <td><span class="fa-chip {{ $book->book_type === 'financial' ? 'fa-chip-teal' : 'fa-chip-amber' }}">{{ ucfirst($book->book_type) }}</span></td>
                                        <td>{{ $book->current_period ?? '—' }}</td>
                                        <td class="numr">{{ format_number($book->period_depreciation) }}</td>
                                        <td class="numr">{{ format_number($book->accumulated_depreciation) }}</td>
                                        <td class="numr">{{ format_number($book->net_book_value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="text-align:center;padding:2rem;color:var(--muted,#5f7476);font-size:.8125rem">No depreciation books recorded yet. Run depreciation from the Dashboard.</p>
                @endif
            </div>
        </div>

        {{-- Panel: Disposal --}}
        <div class="fa-panel" id="tab-disposal">
            @if ($asset->disposals->count())
                <div class="fa-card">
                    <div class="fa-card-head">
                        <h2>Disposal History</h2>
                    </div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>Method</th><th>NBV</th><th>Proceeds</th><th>Cost</th><th>Gain/Loss</th><th>Status</th><th>Reason</th></tr></thead>
                            <tbody>
                                @foreach ($asset->disposals as $d)
                                    <tr>
                                        <td>{{ $d->disposal_date?->format('d M Y') }}</td>
                                        <td>{{ ucfirst($d->disposal_method) }}</td>
                                        <td class="numr">{{ format_number($d->net_book_value) }}</td>
                                        <td class="numr">{{ format_number($d->proceeds_amount) }}</td>
                                        <td class="numr">{{ format_number($d->disposal_cost) }}</td>
                                        <td class="numr {{ ($d->gain_loss ?? 0) < 0 ? 'neg' : '' }}">{{ format_number($d->gain_loss ?? 0) }}</td>
                                        <td><span class="fa-chip {{ $d->isApproved() ? 'fa-chip-green' : ($d->isRejected() ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $d->status_label }}</span></td>
                                        <td style="max-width:200px">{{ \Illuminate\Support\Str::limit($d->reason, 60) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="fa-card" style="text-align:center;padding:2rem">
                    <p style="color:var(--muted,#5f7476);font-size:.8125rem;margin-bottom:1rem">No disposal records.</p>
                    @if ($canDisposal)
                        <a href="{{ route('accounting.fixed-assets.disposals.create', $asset->id) }}" class="fa-btn fa-btn-primary">Request Disposal</a>
                    @endif
                </div>
            @endif

            @if ($asset->impairments->count())
                <div class="fa-card">
                    <div class="fa-card-head"><h2>Impairment Records</h2></div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>Carrying Value</th><th>Recoverable</th><th>Loss</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($asset->impairments as $imp)
                                    <tr>
                                        <td>{{ $imp->impairment_date?->format('d M Y') }}</td>
                                        <td class="numr">{{ format_number($imp->carrying_value) }}</td>
                                        <td class="numr">{{ format_number($imp->recoverable_amount) }}</td>
                                        <td class="numr neg">{{ format_number($imp->impairment_loss) }}</td>
                                        <td><span class="fa-chip {{ $imp->isApproved() ? 'fa-chip-green' : 'fa-chip-amber' }}">{{ $imp->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($asset->revaluations->count())
                <div class="fa-card">
                    <div class="fa-card-head"><h2>Revaluation Records</h2></div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>Previous Value</th><th>New Value</th><th>Surplus</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($asset->revaluations as $r)
                                    <tr>
                                        <td>{{ $r->revaluation_date?->format('d M Y') }}</td>
                                        <td class="numr">{{ format_number($r->previous_value) }}</td>
                                        <td class="numr">{{ format_number($r->new_value) }}</td>
                                        <td class="numr {{ ($r->surplus_amount ?? 0) < 0 ? 'neg' : '' }}">{{ format_number($r->surplus_amount ?? 0) }}</td>
                                        <td><span class="fa-chip {{ $r->isApproved() ? 'fa-chip-green' : ($r->isRejected() ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $r->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Panel: Transfers --}}
        <div class="fa-panel" id="tab-transfers">
            @if ($asset->transfers->count())
                <div class="fa-card">
                    <div class="fa-card-head"><h2>Transfer History</h2></div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($asset->transfers as $t)
                                    <tr>
                                        <td>{{ $t->transfer_date?->format('d M Y') }}</td>
                                        <td>{{ $t->fromBranch?->name ?? $t->from_location ?? '—' }}</td>
                                        <td>{{ $t->toBranch?->name ?? $t->to_location ?? '—' }}</td>
                                        <td style="max-width:200px">{{ \Illuminate\Support\Str::limit($t->reason, 50) }}</td>
                                        <td><span class="fa-chip {{ $t->isApproved() ? 'fa-chip-green' : ($t->isRejected() ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $t->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="fa-card" style="text-align:center;padding:2rem">
                    <p style="color:var(--muted,#5f7476);font-size:.8125rem;margin-bottom:1rem">No transfer records.</p>
                    @if ($canTransfer)
                        <a href="{{ route('accounting.fixed-assets.transfers.create', $asset->id) }}" class="fa-btn fa-btn-primary">Request Transfer</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Panel: Maintenance --}}
        <div class="fa-panel" id="tab-maintenance">
            @if ($asset->maintenanceRecords->count())
                <div class="fa-card">
                    <div class="fa-card-head"><h2>Maintenance Records</h2></div>
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Date</th><th>Type</th><th>Cost</th><th>Provider</th><th>Status</th><th>Notes</th></tr></thead>
                            <tbody>
                                @foreach ($asset->maintenanceRecords as $m)
                                    <tr>
                                        <td>{{ $m->maintenance_date?->format('d M Y') }}</td>
                                        <td>{{ $m->type_label }}</td>
                                        <td class="numr">{{ format_number($m->cost) }}</td>
                                        <td>{{ $m->provider ?? '—' }}</td>
                                        <td><span class="fa-chip {{ $m->status === 'completed' ? 'fa-chip-teal' : ($m->status === 'cancelled' ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $m->status_label }}</span></td>
                                        <td style="max-width:200px">{{ \Illuminate\Support\Str::limit($m->description ?? $m->work_performed, 50) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="fa-card" style="text-align:center;padding:2rem">
                    <p style="color:var(--muted,#5f7476);font-size:.8125rem;margin-bottom:.5rem">No maintenance records.</p>
                </div>
            @endif

            @if ($asset->isActive() || $asset->isDraft())
                <div class="fa-card" style="margin-top:1rem">
                    <div class="fa-card-head"><h2>Add Maintenance Record</h2></div>
                    <form method="POST" action="{{ route('accounting.fixed-assets.maintenance.store', $asset->id) }}" class="fa-detail-grid">
                        @csrf
                        <div class="fa-field">
                            <label class="fa-label">Type</label>
                            <select name="type" class="input" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="unscheduled">Unscheduled</option>
                                <option value="repair">Repair</option>
                            </select>
                        </div>
                        <div class="fa-field">
                            <label class="fa-label">Date</label>
                            <input type="date" name="maintenance_date" required class="input" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="fa-field">
                            <label class="fa-label">Next Due Date</label>
                            <input type="date" name="next_due_date" class="input">
                        </div>
                        <div class="fa-field">
                            <label class="fa-label">Provider</label>
                            <input type="text" name="provider" maxlength="255" class="input" placeholder="Service provider">
                        </div>
                        <div class="fa-field">
                            <label class="fa-label">Cost</label>
                            <input type="number" name="cost" min="0" step="0.01" class="input" placeholder="0.00">
                        </div>
                        <div class="fa-field">
                            <label class="fa-label">Description</label>
                            <input type="text" name="description" maxlength="5000" class="input" placeholder="What was done">
                        </div>
                        <div class="fa-field fa-field--action">
                            <button type="submit" class="fa-btn fa-btn-primary">Add Maintenance</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="fa-card" style="margin-top:1rem">
                <div class="fa-card-head"><h2>Insurance Policies</h2></div>
                @if ($asset->insurancePolicies->count())
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Provider</th><th>Policy #</th><th>Premium</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($asset->insurancePolicies as $ins)
                                    <tr>
                                        <td>{{ $ins->provider }}</td>
                                        <td class="mono">{{ $ins->policy_number }}</td>
                                        <td class="numr">{{ format_number($ins->annual_premium) }}</td>
                                        <td>{{ $ins->start_date?->format('d M Y') }}</td>
                                        <td>{{ $ins->end_date?->format('d M Y') }}</td>
                                        <td><span class="fa-chip {{ $ins->status === 'active' ? 'fa-chip-teal' : 'fa-chip-gray' }}">{{ $ins->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="text-align:center;padding:1.5rem;color:var(--muted,#5f7476);font-size:.8125rem">No insurance policies.</p>
                @endif
                @if ($asset->isActive())
                    <div style="padding:1rem;border-top:1px solid var(--line,#e2ecec)">
                        <h3 style="font-size:.875rem;font-weight:600;margin-bottom:.75rem">Add Insurance Policy</h3>
                        <form method="POST" action="{{ route('accounting.fixed-assets.insurance.store', $asset->id) }}" class="fa-detail-grid">
                            @csrf
                            <div class="fa-field"><label class="fa-label">Provider</label><input type="text" name="provider" required maxlength="255" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Policy #</label><input type="text" name="policy_number" required maxlength="100" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Start Date</label><input type="date" name="start_date" required class="input"></div>
                            <div class="fa-field"><label class="fa-label">End Date</label><input type="date" name="end_date" required class="input"></div>
                            <div class="fa-field"><label class="fa-label">Coverage Amount</label><input type="number" name="coverage_amount" min="0" step="0.01" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Annual Premium</label><input type="number" name="annual_premium" min="0" step="0.01" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Notes</label><input type="text" name="notes" maxlength="5000" class="input"></div>
                            <div class="fa-field fa-field--action"><button type="submit" class="fa-btn fa-btn-primary">Add Policy</button></div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="fa-card" style="margin-top:1rem">
                <div class="fa-card-head"><h2>Warranties</h2></div>
                @if ($asset->warranties->count())
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Provider</th><th>Warranty #</th><th>Start</th><th>End</th><th>Status</th><th>Terms</th></tr></thead>
                            <tbody>
                                @foreach ($asset->warranties as $w)
                                    <tr>
                                        <td>{{ $w->provider }}</td>
                                        <td class="mono">{{ $w->warranty_number ?? '—' }}</td>
                                        <td>{{ $w->start_date?->format('d M Y') }}</td>
                                        <td>{{ $w->end_date?->format('d M Y') }}</td>
                                        <td><span class="fa-chip {{ $w->status === 'active' ? 'fa-chip-teal' : 'fa-chip-gray' }}">{{ ucfirst($w->status) }}</span></td>
                                        <td style="max-width:200px">{{ \Illuminate\Support\Str::limit($w->terms, 50) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="text-align:center;padding:1.5rem;color:var(--muted,#5f7476);font-size:.8125rem">No warranties.</p>
                @endif
                @if ($asset->isActive())
                    <div style="padding:1rem;border-top:1px solid var(--line,#e2ecec)">
                        <h3 style="font-size:.875rem;font-weight:600;margin-bottom:.75rem">Add Warranty</h3>
                        <form method="POST" action="{{ route('accounting.fixed-assets.warranty.store', $asset->id) }}" class="fa-detail-grid">
                            @csrf
                            <div class="fa-field"><label class="fa-label">Provider</label><input type="text" name="provider" required maxlength="255" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Warranty #</label><input type="text" name="warranty_number" maxlength="100" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Start Date</label><input type="date" name="start_date" required class="input"></div>
                            <div class="fa-field"><label class="fa-label">End Date</label><input type="date" name="end_date" required class="input"></div>
                            <div class="fa-field"><label class="fa-label">Terms</label><input type="text" name="terms" maxlength="5000" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Contact Info</label><input type="text" name="contact_info" maxlength="5000" class="input"></div>
                            <div class="fa-field fa-field--action"><button type="submit" class="fa-btn fa-btn-primary">Add Warranty</button></div>
                        </form>
                    </div>
                @endif
            </div>

            <div class="fa-card" style="margin-top:1rem">
                <div class="fa-card-head"><h2>Custody Transfers</h2></div>
                @if ($asset->isActive())
                    <div style="padding:1rem">
                        <h3 style="font-size:.875rem;font-weight:600;margin-bottom:.75rem">Record Custody Handover</h3>
                        <form method="POST" action="{{ route('accounting.fixed-assets.custody.store', $asset->id) }}" class="fa-detail-grid">
                            @csrf
                            <div class="fa-field"><label class="fa-label">From Custodian</label><input type="text" name="from_custodian" maxlength="255" class="input" value="{{ $asset->custodian ?? '' }}"></div>
                            <div class="fa-field"><label class="fa-label">To Custodian</label><input type="text" name="to_custodian" required maxlength="255" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Handover Date</label><input type="date" name="handover_date" required class="input" value="{{ date('Y-m-d') }}"></div>
                            <div class="fa-field"><label class="fa-label">Reason</label><input type="text" name="reason" maxlength="5000" class="input"></div>
                            <div class="fa-field"><label class="fa-label">Condition Notes</label><input type="text" name="condition_notes" maxlength="5000" class="input"></div>
                            <div class="fa-field fa-field--action"><button type="submit" class="fa-btn fa-btn-primary">Record Handover</button></div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel: Components --}}
        <div class="fa-panel" id="tab-components">
            <div class="fa-card">
                <div class="fa-card-head"><h2>Components</h2></div>
                @if ($asset->components->count())
                    <div class="fa-table-wrap">
                        <table class="fa-table">
                            <thead><tr><th>Name</th><th>Description</th><th>Cost</th><th>Accum. Dep.</th><th>NBV</th><th>Method</th><th>Useful Life</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                @foreach ($asset->components as $comp)
                                    <tr>
                                        <td>{{ $comp->name }}</td>
                                        <td>{{ $comp->description ?? '—' }}</td>
                                        <td class="numr">{{ format_number($comp->cost) }}</td>
                                        <td class="numr">{{ format_number($comp->accumulated_depreciation) }}</td>
                                        <td class="numr">{{ format_number($comp->net_book_value) }}</td>
                                        <td>{{ $comp->depreciation_method ?? '—' }}</td>
                                        <td>{{ $comp->useful_life ? $comp->useful_life . ' mo' : '—' }}</td>
                                        <td><span class="fa-chip {{ $comp->isActive() ? 'fa-chip-teal' : 'fa-chip-gray' }}">{{ ucfirst($comp->status) }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('accounting.fixed-assets.components.destroy', $comp->id) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-sm" onclick="fbConfirmSubmit(event, 'Remove component {{ $comp->name }}?', {type:'danger'})">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="text-align:center;padding:2rem;color:var(--muted,#5f7476);font-size:.8125rem;margin-bottom:1rem">No components recorded.</p>
                @endif

                @if ($asset->isActive() || $asset->isDraft())
                    <div style="padding:1rem;border-top:1px solid var(--line,#e2ecec)">
                        <h3 style="font-size:.875rem;font-weight:600;margin-bottom:.75rem">Add Component</h3>
                        <form method="POST" action="{{ route('accounting.fixed-assets.components.store', $asset->id) }}" class="fa-detail-grid">
                            @csrf
                            <div class="fa-field">
                                <label class="fa-label">Name</label>
                                <input type="text" name="name" required maxlength="255" class="input" placeholder="e.g. Engine">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Description</label>
                                <input type="text" name="description" maxlength="5000" class="input" placeholder="Optional">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Cost</label>
                                <input type="number" name="cost" required min="0" step="0.01" class="input" placeholder="0.00">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Dep. Method</label>
                                <input type="text" name="depreciation_method" maxlength="50" class="input" placeholder="e.g. STRAIGHT_LINE">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Useful Life (months)</label>
                                <input type="number" name="useful_life" min="1" max="600" class="input">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Residual Value</label>
                                <input type="number" name="residual_value" min="0" step="0.01" class="input" placeholder="0.00">
                            </div>
                            <div class="fa-field">
                                <label class="fa-label">Start Date</label>
                                <input type="date" name="start_date" class="input">
                            </div>
                            <div class="fa-field fa-field--action">
                                <button type="submit" class="fa-btn fa-btn-primary">Add Component</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel: History --}}
        <div class="fa-panel" id="tab-history">
            <div class="fa-card">
                <div class="fa-card-head"><h2>Audit Trail</h2></div>
                @if ($history->count())
                    <div class="fa-tl">
                        @foreach ($history as $h)
                            <div class="fa-tl-item">
                                <div class="fa-tl-dot {{ $h->action === 'rejected' ? 'red' : ($h->action === 'created' ? '' : 'amber') }}"></div>
                                <div class="fa-tl-date">{{ $h->created_at?->format('d M Y H:i') }}</div>
                                <div class="fa-tl-text">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $h->action)) }}</strong>
                                    @if ($h->description) — {{ $h->description }} @endif
                                    @if ($h->user) <span style="color:var(--muted)">by {{ $h->user->name }}</span> @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="text-align:center;padding:2rem;color:var(--muted,#5f7476);font-size:.8125rem">No history records.</p>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.fa-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = this.dataset.tab;
                document.querySelectorAll('.fa-tab').forEach(function(t) { t.classList.remove('active'); });
                document.querySelectorAll('.fa-panel').forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById('tab-' + target).classList.add('active');
            });
        });
    </script>
    @endpush
</x-app-layout>
