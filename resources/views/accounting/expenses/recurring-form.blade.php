<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @php
        $isEdit = isset($template) && $template;
        $formAction = $isEdit
            ? route('accounting.expenses.recurring.update', $template)
            : route('accounting.expenses.recurring.store');
        $formMethod = $isEdit ? 'PUT' : 'POST';
        $formTitle = $isEdit ? __('Edit Recurring Template') : __('New Recurring Template');
        $cancelRoute = route('accounting.expenses.recurring.index');
    @endphp

    <div class="ex-suite wrap" style="display:flex;justify-content:center">
        <div class="card" style="max-width:860px;width:100%">
            <div class="card-h">
                <h2>{{ $formTitle }}</h2>
                @if($isEdit)
                    <span class="badge {{ $template->is_active ? 'b-act' : 'b-inact' }}" style="margin-left:auto"><span class="bdot"></span>{{ $template->is_active ? __('Active') : __('Paused') }}</span>
                @endif
            </div>
            <div class="card-sec">
                <form method="POST" action="{{ $formAction }}" id="recurring-form">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    @if($errors->any())
                        <div class="note-warn" role="alert" style="margin-bottom:14px">
                            @foreach($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="g4" style="grid-template-columns:1fr 1fr">
                        <div class="field" style="grid-column:1/-1">
                            <label>{{ __('Template Name') }} *</label>
                            <input class="input h44" name="name" value="{{ old('name', $isEdit ? $template->name : '') }}" required placeholder="{{ __('e.g. Monthly Office Rent') }}">
                        </div>
                        <div class="field">
                            <label>{{ __('Category') }}</label>
                            <select class="input h44" name="category_id">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $isEdit ? $template->category_id : '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Payee / Vendor') }}</label>
                            <select class="input h44" name="vendor_id">
                                <option value="">{{ __('Select vendor') }}</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $isEdit ? $template->vendor_id : '') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="grid-column:1/-1">
                            <label>{{ __('Description') }}</label>
                            <textarea class="input" name="description" rows="2" placeholder="{{ __('What does this recurring expense cover?') }}">{{ old('description', $isEdit ? $template->description : '') }}</textarea>
                        </div>
                        <div class="field">
                            <label>{{ __('Amount') }} ({{ $cs }}) *</label>
                            <input class="input h44" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $isEdit ? $template->amount : '') }}" required placeholder="0.00">
                        </div>
                        <div class="field">
                            <label>{{ __('Expense Account') }} *</label>
                            <select class="input h44" name="expense_account_id" required>
                                <option value="">{{ __('Select account') }}</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('expense_account_id', $isEdit ? $template->expense_account_id : '') == $account->id ? 'selected' : '' }}>{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Frequency') }} *</label>
                            <select class="input h44" name="frequency" required>
                                @foreach($frequencies as $fkey => $flabel)
                                    <option value="{{ $fkey }}" {{ old('frequency', $isEdit ? $template->frequency : '') === $fkey ? 'selected' : '' }}>{{ $flabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Interval') }} *</label>
                            <input class="input h44" type="number" step="1" min="1" max="365" name="interval" value="{{ old('interval', $isEdit ? $template->interval : 1) }}" required>
                        </div>
                        <div class="field">
                            <label>{{ __('Start Date') }} *</label>
                            <input class="input h44" type="date" name="start_date" value="{{ old('start_date', $isEdit ? ($template->start_date?->format('Y-m-d') ?? '') : now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="field">
                            <label>{{ __('End Date') }}</label>
                            <input class="input h44" type="date" name="end_date" value="{{ old('end_date', $isEdit && $template->end_date ? $template->end_date->format('Y-m-d') : '') }}">
                        </div>
                        <div class="field">
                            <label>{{ __('Branch') }}</label>
                            <select class="input h44" name="branch_id">
                                <option value="">{{ __('All / none') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $isEdit ? $template->branch_id : '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Cost Centre') }}</label>
                            <select class="input h44" name="cost_center_id">
                                <option value="">{{ __('All / none') }}</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ old('cost_center_id', $isEdit ? $template->cost_center_id : '') == $cc->id ? 'selected' : '' }}>{{ $cc->code }} — {{ $cc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Currency') }}</label>
                            <select class="input h44" name="currency">
                                <option value="">{{ __('Base currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" {{ old('currency', $isEdit ? $template->currency : '') === $currency->code ? 'selected' : '' }}>{{ $currency->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Exchange Rate') }}</label>
                            <input class="input h44" type="number" step="0.000001" min="0" name="exchange_rate" value="{{ old('exchange_rate', $isEdit ? $template->exchange_rate : '') }}" placeholder="1.000000">
                        </div>
                    </div>

                    @if($isEdit)
                        <div class="toggle-row" style="margin-top:16px">
                            <label class="toggle-text">
                                <strong>{{ __('Active') }}</strong>
                                <span>{{ __('Generate expenses on schedule.') }}</span>
                            </label>
                            <label class="toggle-ui">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" class="toggle-input" name="is_active" value="1" {{ old('is_active', $template->is_active ? 1 : 0) ? 'checked' : '' }}>
                                <span class="toggle-track"><span class="toggle-thumb"></span></span>
                            </label>
                        </div>
                    @endif

                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                        <a href="{{ $cancelRoute }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-cta">{{ $isEdit ? __('Save Changes') : __('Create Template') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
