<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.account-classification.index') }}">Classification</a> <span>›</span> <span class="here">{{ isset($classification) ? 'Edit' : 'Create' }}</span>
            </nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.account-classification.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-pad">
                <form method="POST" action="{{ route('accounting.account-classification.store') }}">
                    @csrf
                    <div class="ac-g2">
                        <div class="ac-f">
                            <label>Classification Name</label>
                            <input class="in" name="name" value="{{ old('name', $classification['name'] ?? '') }}" placeholder="e.g. Current Assets" required>
                        </div>
                        <div class="ac-f">
                            <label>Statement</label>
                            <select class="in" name="statement" required>
                                <option value="balance_sheet" {{ (old('statement', $classification['statement'] ?? '') === 'balance_sheet') ? 'selected' : '' }}>Balance Sheet</option>
                                <option value="income_statement" {{ (old('statement', $classification['statement'] ?? '') === 'income_statement') ? 'selected' : '' }}>Income Statement</option>
                            </select>
                        </div>
                        <div class="ac-f">
                            <label>Section</label>
                            <input class="in" name="section" value="{{ old('section', $classification['section'] ?? '') }}" placeholder="e.g. current_assets" required>
                        </div>
                        <div class="ac-f">
                            <label>Display Order</label>
                            <input class="in" type="number" name="display_order" value="{{ old('display_order', $classification['display_order'] ?? 10) }}" required>
                        </div>
                    </div>
                    <div class="ac-f" style="margin-top:16px">
                        <label>Accounts</label>
                        <div style="max-height:300px;overflow-y:auto;border:1px solid var(--ac-border);border-radius:12px;padding:12px">
                            @forelse($accounts as $account)
                            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--ac-line);cursor:pointer">
                                <input type="checkbox" name="account_ids[]" value="{{ $account->id }}">
                                <span class="ac-mono" style="font-size:12px">{{ $account->code }}</span>
                                <span style="font-size:13px">{{ $account->name }}</span>
                            </label>
                            @empty
                            <div class="ac-em">No accounts available.</div>
                            @endforelse
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <a href="{{ route('accounting.account-classification.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">Save Classification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
