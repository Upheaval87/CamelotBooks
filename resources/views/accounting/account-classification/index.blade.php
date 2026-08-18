<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Account Classification</h1>
            <div class="ac-sub">Map accounts to Balance Sheet and Income Statement sections.</div>
        </div>

        <form method="POST" action="{{ route('accounting.account-classification.update') }}">
            @csrf
            @method('PATCH')

            <div class="ac-card" style="margin-bottom:22px">
                <div class="ac-card-h">
                    <h2>Accounts</h2>
                    <div class="right">
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">Save Classifications</button>
                    </div>
                </div>
                <div class="ac-li-wrap">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th style="width:12%">Code</th>
                                <th style="width:22%">Account Name</th>
                                <th style="width:14%">Type</th>
                                <th style="width:18%">Cash Flow Section</th>
                                <th style="width:14%">Non-Cash</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                            <tr>
                                <td class="ac-mono">{{ $account->code }}</td>
                                <td style="font-weight:600">{{ $account->name }}</td>
                                <td><span class="ac-tchip">{{ $account->type ?? '—' }}</span></td>
                                <td>
                                    <select class="ac-ci" name="cash_flow_sections[{{ $account->id }}]">
                                        <option value="">Not applicable</option>
                                        @foreach(['Operating' => 'operating', 'Investing' => 'investing', 'Financing' => 'financing'] as $label => $val)
                                        <option value="{{ $val }}" {{ ($account->cash_flow_section ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                        <input type="checkbox" name="is_non_cash[{{ $account->id }}]" value="1" {{ ($account->is_non_cash ?? false) ? 'checked' : '' }}>
                                        <span style="font-size:11px;color:var(--ac-muted)">Non-cash</span>
                                    </label>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="ac-em" style="text-align:center;padding:40px">No accounts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
