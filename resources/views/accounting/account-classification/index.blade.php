<x-app-layout>
    <div class="ac-wrap">
        <div class="page-head">
            <div>
                <h1>Account Classification</h1>
                <div class="sub">Map accounts to Balance Sheet and Income Statement sections.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('accounting.account-classification.update') }}">
            @csrf
            @method('PATCH')

            <div class="ac-card" style="margin-bottom:22px">
                <div class="ac-card-h">
                    <span class="ac-ic">&#128204;</span>
                    <h2>Accounts</h2>
                    <div class="right">
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">Save Classifications</button>
                    </div>
                </div>
                <div class="ac-li-wrap">
                    <table class="ac-table">
                        <thead class="ac-thead">
                            <tr>
                                <th style="width:9%">Code</th>
                                <th style="width:30%">Account Name</th>
                                <th style="width:11%">Type</th>
                                <th style="width:30%">Cash Flow Section</th>
                                <th style="width:20%;text-align:center">Non-Cash</th>
                            </tr>
                        </thead>
                        <tbody class="ac-tbody">
                            @forelse($accounts as $account)
                            @php
                                $typeClass = match($account->type) {
                                    'asset' => 'type-asset',
                                    'liability' => 'type-liability',
                                    'equity' => 'type-equity',
                                    'income' => 'type-income',
                                    'expense' => 'type-expense',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td class="mono">{{ $account->code }}</td>
                                <td class="name">{{ $account->name }}</td>
                                <td><span class="ac-tchip {{ $typeClass }}">{{ $account->type ?? '—' }}</span></td>
                                <td>
                                    <select class="ac-tsel" name="cash_flow_sections[{{ $account->id }}]">
                                        <option value="">Not applicable</option>
                                        @foreach(['Operating' => 'operating', 'Investing' => 'investing', 'Financing' => 'financing'] as $label => $val)
                                        <option value="{{ $val }}" {{ ($account->cash_flow_section ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="text-align:center">
                                    <input type="checkbox" name="is_non_cash[{{ $account->id }}]" value="1" {{ ($account->is_non_cash ?? false) ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--ac-sec)">
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="ac-empty">
                                        <div class="ac-empty-ic">&#128204;</div>
                                        No accounts found.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ac-card ac-actionbar">
                <a href="{{ route('accounting.account-classification.index') }}" class="ac-btn ac-btn-ghost">Discard</a>
                <button type="submit" class="ac-btn ac-btn-cta">Save Classifications</button>
            </div>
        </form>
    </div>
</x-app-layout>
