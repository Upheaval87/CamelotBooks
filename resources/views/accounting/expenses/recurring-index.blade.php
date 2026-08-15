<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('Recurring Expenses') }}</h1>
                <div class="sub">{{ __('Automated expense templates for repeat costs.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <details class="more">
                    <summary class="btn btn-ghost btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 9h12M6 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ __('More') }}
                    </summary>
                    <div class="more-menu">
                        <a class="more-item" href="{{ route('accounting.expenses.dashboard') }}">{{ __('Expense Dashboard') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.index') }}">{{ __('All Expenses') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.categories.index') }}">{{ __('Expense Categories') }}</a>
                    </div>
                </details>
                <a href="{{ route('accounting.expenses.recurring.create') }}" class="btn btn-cta btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('New Template') }}
                </a>
            </div>
        </div>

        <section class="card">
            <div class="card-sec" style="padding-top:6px">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:16%">{{ __('Name') }}</th>
                                <th style="width:10%">{{ __('Frequency') }}</th>
                                <th style="width:10%">{{ __('Every') }}</th>
                                <th style="width:12%">{{ __('Category') }}</th>
                                <th style="width:14%">{{ __('Payee') }}</th>
                                <th class="num" style="width:10%">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:12%">{{ __('Status') }}</th>
                                <th style="width:16%">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td class="em"><strong>{{ $template->name }}</strong></td>
                                    <td class="em">{{ $template->frequencyLabel() }}</td>
                                    <td class="em">{{ $template->interval }} {{ Str::plural(strtolower($template->frequencyLabel()), $template->interval) }}</td>
                                    <td class="em">{{ $template->category?->name ?? '—' }}</td>
                                    <td class="em">{{ $template->vendor?->name ?? '—' }}</td>
                                    <td class="numr">{{ format_number($template->amount) }}</td>
                                    <td>
                                        <span class="badge {{ $template->is_active ? 'b-act' : 'b-inact' }}"><span class="bdot"></span>{{ $template->is_active ? __('Active') : __('Paused') }}</span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            @can('expense-recurring.edit')
                                                <a class="btn btn-sec btn-xs" href="{{ route('accounting.expenses.recurring.edit', $template) }}">{{ __('Edit') }}</a>
                                                <form method="POST" action="{{ route('accounting.expenses.recurring.toggle', $template) }}" onsubmit="return fbConfirmSubmit(event, '{{ $template->is_active ? __('Pause this template?') : __('Activate this template?') }}', { type: 'action' })">
                                                    @csrf
                                                    <button class="btn btn-ghost btn-xs" type="submit">{{ $template->is_active ? __('Pause') : __('Activate') }}</button>
                                                </form>
                                            @endcan
                                            @can('expense-recurring.delete')
                                                <form method="POST" action="{{ route('accounting.expenses.recurring.destroy', $template) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this template?') }}', { type: 'danger' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-ghost btn-xs" type="submit">{{ __('Delete') }}</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="em" style="text-align:center;padding:28px">{{ __('No recurring expense templates yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
