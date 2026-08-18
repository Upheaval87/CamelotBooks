<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.exchange-rates.index') }}">Exchange Rates</a> <span>›</span> <span class="here">{{ isset($exchangeRate) ? 'Edit' : 'New' }}</span>
            </nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.exchange-rates.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
            </div>
        </div>

        <h1 class="ac-page-head-title">New Exchange Rate</h1>

        <div class="ac-card">
            <div class="ac-pad">
                <form method="POST" action="{{ isset($exchangeRate) ? route('accounting.exchange-rates.update', $exchangeRate) : route('accounting.exchange-rates.store') }}">
                    @csrf
                    @if(isset($exchangeRate)) @method('PUT') @endif
                    <div class="ac-g2">
                        <div class="ac-f">
                            <label>From Currency</label>
                            <select class="in" name="currency_from" required>
                                <option value="">Select currency</option>
                                @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}" {{ (old('currency_from', $exchangeRate?->currency_from) === $cur->code) ? 'selected' : '' }}>{{ $cur->code }} — {{ $cur->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ac-f">
                            <label>To Currency</label>
                            <select class="in" name="currency_to" required>
                                <option value="">Select currency</option>
                                @foreach($currencies as $cur)
                                <option value="{{ $cur->code }}" {{ (old('currency_to', $exchangeRate?->currency_to) === $cur->code) ? 'selected' : '' }}>{{ $cur->code }} — {{ $cur->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ac-f">
                            <label>Rate</label>
                            <input class="in" type="number" step="0.000001" name="rate" value="{{ old('rate', $exchangeRate?->rate) }}" placeholder="0.000000" required>
                        </div>
                        <div class="ac-f">
                            <label>Effective Date</label>
                            <input class="in" type="date" name="effective_date" value="{{ old('effective_date', $exchangeRate?->effective_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <a href="{{ route('accounting.exchange-rates.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">{{ isset($exchangeRate) ? 'Update Rate' : 'Create Rate' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
