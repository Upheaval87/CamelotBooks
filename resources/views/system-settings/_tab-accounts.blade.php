<form method="POST" action="{{ route('system-settings.update-account-mappings') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">04 · DEFAULT ACCOUNT MAPPINGS</div>
        <div class="settings-section-title">Default Account Mappings</div>
        <p class="settings-section-desc">Map system operations to your Chart of Accounts. Every journal entry posted by the system uses these mappings. If a mapping is empty, the relevant operation will fail until one is assigned.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="settings-card">
        @php
            $accountOptions = $accounts->mapWithKeys(fn($a) => [$a->id => "{$a->code} — {$a->name}"])->toArray();
            $requiredKeys = ['sales_revenue', 'accounts_receivable', 'inventory', 'cost_of_goods_sold', 'accounts_payable', 'cash_on_hand'];
        @endphp
        @foreach(\App\Models\DefaultAccountMapping::availableKeys() as $key => $label)
            @php
                $currentVal = $mappings[$key] ?? null;
                $isRequired = in_array($key, $requiredKeys);
                $isMapped = !is_null($currentVal);
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start py-3 border-b border-line last:border-0">
                <div class="md:col-span-1">
                    <div class="settings-field-label">{{ $label }}</div>
                    <span class="text-xs text-ink-faint">{{ $key }}</span>
                </div>
                <div class="md:col-span-2">
                    <select name="{{ $key }}" class="settings-field-input">
                        <option value="">— Not mapped —</option>
                        @foreach($accountOptions as $id => $optLabel)
                            <option value="{{ $id }}" {{ $currentVal == $id ? 'selected' : '' }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                    @if($isMapped)
                        <p class="settings-mapping-ok">✓ Mapped</p>
                    @elseif($isRequired)
                        <p class="settings-mapping-warn">Required — will block journal posting</p>
                    @else
                        <p class="settings-field-hint">Optional mapping</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save Account Mappings</button>
    </div>
</form>
