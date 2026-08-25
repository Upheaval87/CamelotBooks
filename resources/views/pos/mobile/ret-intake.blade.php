@extends('layouts.pos-mobile', ['title' => 'Bottle Intake'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Bottle Intake</div>
        <div class="pos-m-greeting-sub">Accept containers · Issue a BRR receipt</div>
    </div>

    @if($errors->any())
        <div class="pos-m-toast pos-m-toast--error" style="position:static;transform:none;width:100%;margin-bottom:.75rem;">
            {{ $errors->first() }}
            <button class="pos-m-toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    <form method="POST" action="{{ route('pos.m.ret-intake.store') }}" id="retake-form">
        @csrf

        {{-- Customer --}}
        <div class="pos-m-section-title">Customer</div>
        <div class="pos-m-field" style="position:relative">
            <input type="text" class="pos-m-input" id="ret-customer-search"
                   placeholder="Search customer…" autocomplete="off">
            <input type="hidden" name="customer_id" id="ret-customer-id" value="{{ old('customer_id') }}">
            <div class="pos-m-customer-chip" id="ret-customer-chip" style="display:{{ old('customer_id') ? 'flex' : 'none' }}">
                <span id="ret-customer-name"></span>
                <button type="button" class="pos-m-chip-x" onclick="clearRetCustomer()">&times;</button>
            </div>
            <div class="pos-m-customer-list" id="ret-customer-list"></div>
        </div>

        {{-- Container Select --}}
        <div class="pos-m-section-title">Container</div>
        <div class="pos-m-field">
            <select name="product_id" class="pos-m-select" id="ret-container" required>
                <option value="">Select container…</option>
                @foreach($containers as $c)
                    <option value="{{ $c->id }}"
                            data-deposit="{{ $c->returnable?->deposit_value ?? 0 }}"
                            data-type="{{ $c->returnable?->container_type ?? 'bottle' }}"
                            data-window="{{ $c->returnable?->return_window_days ?? 0 }}"
                            {{ old('product_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->sku }})
                    </option>
                @endforeach
            </select>
            <div class="pos-m-meta" id="ret-container-meta" style="display:none">
                <span id="ret-deposit-each"></span>
                <span id="ret-container-type"></span>
            </div>
        </div>

        {{-- Quantity Steppers --}}
        <div class="pos-m-section-title">Quantity</div>
        <div class="pos-m-qty-row">
            <button type="button" class="pos-m-qty-btn" onclick="retAdjust(-1)">−</button>
            <input type="number" name="bottle_count" id="ret-qty" class="pos-m-qty-val"
                   min="1" max="9999" value="{{ old('bottle_count', 1) }}" required>
            <button type="button" class="pos-m-qty-btn" onclick="retAdjust(1)">+</button>
        </div>

        {{-- Live Total --}}
        <div class="pos-m-total-row" id="ret-total-row" style="display:none">
            <span class="pos-m-total-label">Total credit</span>
            <span class="pos-m-total-value" id="ret-total-value">K 0.00</span>
        </div>

        {{-- Credit To Chips --}}
        <div class="pos-m-section-title">Credit to</div>
        <div class="pos-m-chips">
            <button type="button" class="pos-m-chip pos-m-chip--on" data-val="store_credit" onclick="retCreditTo(this)">Store credit</button>
            <button type="button" class="pos-m-chip" data-val="cash_refund" onclick="retCreditTo(this)">Cash refund</button>
        </div>
        <input type="hidden" name="credit_to" id="ret-credit-to" value="store_credit">

        {{-- Notes --}}
        <div class="pos-m-section-title" style="margin-top:.75rem">Notes <span style="font-weight:400;color:#9AAEAE">(optional)</span></div>
        <div class="pos-m-field">
            <input type="text" name="notes" class="pos-m-input" maxlength="500"
                   value="{{ old('notes') }}" placeholder="Condition, reason, etc.">
        </div>

        {{-- Confirm --}}
        <button type="submit" class="pos-m-btn pos-m-btn--solid pos-m-btn--block" id="ret-confirm" disabled
                style="margin-top:1rem">
            Confirm Return
        </button>
    </form>

    @include('pos.mobile._bottom-nav', ['active' => 'home'])
</div>

<script>
document.addEventListener('alpine:init', () => {});

@php
    $containerData = $containers->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'sku' => $c->sku,
        'deposit' => $c->returnable?->deposit_value,
        'type' => $c->returnable?->container_type,
        'window' => $c->returnable?->return_window_days,
    ])->values()->toArray();
    $customerData = $customers->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
    ])->values()->toArray();
@endphp

const containers = {!! \Illuminate\Support\Js::from($containerData) !!};
const customers  = {!! \Illuminate\Support\Js::from($customerData) !!};

let retDeposit = 0;
const retContainer = document.getElementById('ret-container');
const retQty       = document.getElementById('ret-qty');
const retTotal     = document.getElementById('ret-total-value');
const retTotalRow  = document.getElementById('ret-total-row');
const retConfirm   = document.getElementById('ret-confirm');
const retMeta      = document.getElementById('ret-container-meta');

retContainer.addEventListener('change', () => {
    const opt = retContainer.selectedOptions[0];
    retDeposit = parseFloat(opt?.dataset?.deposit || 0);
    const type = opt?.dataset?.type || '';
    document.getElementById('ret-deposit-each').textContent = 'K ' + retDeposit.toFixed(2) + ' each';
    document.getElementById('ret-container-type').textContent = type;
    retMeta.style.display = opt?.value ? 'flex' : 'none';
    retUpdateTotal();
});

retQty.addEventListener('input', retUpdateTotal);

function retAdjust(d) {
    const v = Math.max(1, parseInt(retQty.value || 1) + d);
    retQty.value = v;
    retUpdateTotal();
}

function retUpdateTotal() {
    const qty = parseInt(retQty.value || 1);
    const total = qty * retDeposit;
    retTotal.textContent = 'K ' + total.toFixed(2);
    retTotalRow.style.display = retContainer.value ? 'flex' : 'none';
    retConfirm.disabled = !retContainer.value;
}

function retCreditTo(btn) {
    document.querySelectorAll('.pos-m-chips .pos-m-chip').forEach(c => {
        c.classList.remove('pos-m-chip--on');
    });
    btn.classList.add('pos-m-chip--on');
    document.getElementById('ret-credit-to').value = btn.dataset.val;
}

// Customer search
const retSearch = document.getElementById('ret-customer-search');
const retList   = document.getElementById('ret-customer-list');
const retCid    = document.getElementById('ret-customer-id');
const retChip   = document.getElementById('ret-customer-chip');
const retCname  = document.getElementById('ret-customer-name');

retSearch.addEventListener('input', () => {
    const q = retSearch.value.trim().toLowerCase();
    if (q.length < 2) { retList.style.display = 'none'; return; }
    const matches = customers.filter(c => c.name.toLowerCase().includes(q)).slice(0, 8);
    if (!matches.length) { retList.style.display = 'none'; return; }
    retList.innerHTML = matches.map(c => '<div class="pos-m-customer-opt" onclick="pickRetCustomer(' + c.id + ',\'' + c.name.replace(/'/g, "\\'") + '\')">' + c.name + '</div>').join('');
    retList.style.display = 'block';
});

function pickRetCustomer(id, name) {
    retCid.value = id;
    retCname.textContent = name;
    retChip.style.display = 'flex';
    retSearch.value = '';
    retList.style.display = 'none';
}

function clearRetCustomer() {
    retCid.value = '';
    retChip.style.display = 'none';
}
</script>
@endsection
