<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite ex-suite stage">

                <div class="page-head">
                    <div>
                        <h1>Vendor Centre Settings</h1>
                        <div class="sub">Defaults and preferences for how vendor operations behave.</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.vendors.dashboard') }}" class="btn ghost">Back to Dashboard</a>
                    </div>
                </div>

                <form method="POST" action="{{ route('accounting.vendors.settings.update') }}" class="card" style="max-width:760px; padding:26px">
                    @csrf

                    @if($errors->any())
                        <div class="note-warn" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="g3">
                        <div class="field">
                            <label class="label" for="default_payment_terms">Default Payment Terms</label>
                            <select name="default_payment_terms" id="default_payment_terms" class="input">
                                @foreach(['due_on_receipt' => 'Due on Receipt', 'net_15' => 'Net 15', 'net_30' => 'Net 30', 'net_60' => 'Net 60', 'net_90' => 'Net 90', 'custom' => 'Custom'] as $value => $label)
                                    <option value="{{ $value }}" {{ $settings['default_payment_terms'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="hint">Applied to new vendors when no terms are specified.</p>
                        </div>

                        <div class="field">
                            <label class="label" for="default_currency">Default Currency</label>
                            <input type="text" name="default_currency" id="default_currency" class="input" value="{{ $settings['default_currency'] }}" maxlength="10" placeholder="Leave blank to use system currency">
                            <p class="hint">ISO code, e.g. <code>USD</code>.</p>
                        </div>

                        <div class="field">
                            <label class="label" for="due_soon_days">Due-Soon Window (days)</label>
                            <input type="number" name="due_soon_days" id="due_soon_days" class="input" value="{{ $settings['due_soon_days'] }}" min="1" max="120">
                            <p class="hint">How far ahead bills count as "due soon" on the dashboard.</p>
                        </div>
                    </div>

                    <div class="tbtns" style="margin-top:22px">
                        <button type="submit" class="btn cta">Save Settings</button>
                    </div>
                </form>
            </div>

            @include('accounting.vendors._slim-rail', ['active' => 'settings'])
        </div>
    </div>
</x-app-layout>
