<x-app-layout>
    <div class="bu-wrap">
        <div class="bu-page-head">
            <div>
                <h1 style="font-size:21px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">Budget Templates</h1>
                <div class="sub">Reusable budget templates to speed up budget creation.</div>
            </div>
        </div>

        <x-budgeting-subnav active-tab="dashboard" />

        <div class="bu-g3" style="margin-top:20px">
            <div class="bu-card" style="grid-column:1/-1">
                <div class="bu-card-h">Create Template</div>
                <div class="bu-pad">
                    <form method="POST" action="{{ route('accounting.budgets.templates.store') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        @csrf
                        <div class="bu-f">
                            <label>Template Name <span style="color:var(--red-2)">*</span></label>
                            <input type="text" name="name" class="in" required placeholder="e.g. Standard Operating Budget">
                        </div>
                        <div class="bu-f">
                            <label>Based on Budget</label>
                            <select name="source_budget_id" class="in">
                                <option value="">Empty template</option>
                                @foreach($budgets as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bu-f" style="flex:1">
                            <label>Description</label>
                            <input type="text" name="description" class="in" placeholder="Brief description of this template">
                        </div>
                        <button type="submit" class="bu-btn bu-btn-cta bu-btn-sm">Create Template</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="bu-card" style="margin-top:16px">
            <div class="bu-pad">
                <div class="bu-li-wrap">
                    <table>
                        <thead><tr><th>Template Name</th><th>Description</th><th>Source Budget</th><th>Created</th><th></th></tr></thead>
                        <tbody>
                            @forelse($templates as $tpl)
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)">{{ $tpl->name }}</td>
                                    <td style="font-size:12px;color:var(--muted)">{{ $tpl->description ?? '—' }}</td>
                                    <td>{{ $tpl->sourceBudget?->name ?? '—' }}</td>
                                    <td style="font-size:12px;color:var(--muted)">{{ $tpl->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('accounting.budgets.create') }}?template={{ $tpl->id }}" class="bu-btn bu-btn-ghost bu-btn-sm">Use Template</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="bu-empty">No templates yet. Create one from an existing budget above.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
