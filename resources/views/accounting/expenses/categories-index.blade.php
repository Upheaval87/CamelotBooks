<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('Expense Categories') }}</h1>
                <div class="sub">{{ __('Group expenses for reporting and budget control.') }}</div>
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
                        <a class="more-item" href="{{ route('accounting.expenses.reports') }}">{{ __('Reports') }}</a>
                    </div>
                </details>
                <button type="button" class="btn btn-cta btn-sm" onclick="document.getElementById('new-category').scrollIntoView({behavior:'smooth'})">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('New Category') }}
                </button>
            </div>
        </div>

        <section class="card" id="new-category">
            <div class="card-h">
                <h2>{{ __('Create Expense Category') }}</h2>
            </div>
            <div class="card-sec">
                <form method="POST" action="{{ route('accounting.expenses.categories.store') }}">
                    @csrf
                    @if($errors->any())
                        <div class="note-warn" role="alert" style="margin-bottom:14px">
                            @foreach($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif
                    <div class="g4" style="grid-template-columns:1fr 1fr 1fr auto">
                        <div class="field">
                            <label>{{ __('Name') }} *</label>
                            <input class="input h44" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="{{ __('e.g. Travel') }}">
                        </div>
                        <div class="field">
                            <label>{{ __('Color') }}</label>
                            <input class="input h44" type="color" name="color" value="{{ old('color', '#128F8E') }}" style="padding:4px;height:44px">
                        </div>
                        <div class="field">
                            <label>{{ __('Description') }}</label>
                            <input class="input h44" name="description" value="{{ old('description') }}" maxlength="500" placeholder="{{ __('Optional') }}">
                        </div>
                        <div class="field" style="align-self:flex-end">
                            <button type="submit" class="btn btn-cta h44">{{ __('Add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="card" style="margin-top:16px">
            <div class="card-sec" style="padding-top:6px">
                <div class="li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:22%">{{ __('Name') }}</th>
                                <th style="width:34%">{{ __('Description') }}</th>
                                <th class="num" style="width:12%">{{ __('Expenses') }} ({{ $cs }})</th>
                                <th class="num" style="width:8%">{{ __('Count') }}</th>
                                <th style="width:10%">{{ __('Status') }}</th>
                                <th style="width:14%">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>
                                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $category->color ?? 'var(--sec)' }};margin-right:8px"></span>
                                        <strong>{{ $category->name }}</strong>
                                    </td>
                                    <td class="em">{{ $category->description ?? '—' }}</td>
                                    <td class="numr">{{ format_number($spend[$category->id] ?? 0) }}</td>
                                    <td class="numr">{{ $category->expenses_count }}</td>
                                    <td>
                                        <span class="badge {{ $category->is_active ? 'b-act' : 'b-inact' }}"><span class="bdot"></span>{{ $category->is_active ? __('Active') : __('Inactive') }}</span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                                            <button type="button" class="btn btn-sec btn-xs" onclick="editCategory({{ $category->id }}, {{ json_encode($category->name) }}, {{ json_encode($category->color) }}, {{ json_encode($category->description) }})">{{ __('Edit') }}</button>
                                            @can('expense-categories.delete')
                                                <form method="POST" action="{{ route('accounting.expenses.categories.destroy', $category) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this category?') }}', { type: 'danger' })">
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
                                    <td colspan="6" class="em" style="text-align:center;padding:28px">{{ __('No expense categories yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div id="edit-category-modal" class="cb-modal" style="display:none">
        <div class="cb-modal-backdrop" style="position:fixed;inset:0;background:rgba(10,46,50,.45);z-index:120" onclick="document.getElementById('edit-category-modal').style.display='none'"></div>
        <div class="cb-modal-card" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:121;background:#fff;border-radius:16px;padding:24px;width:min(480px,calc(100vw - 32px))">
            <h3 style="font-size:16px;font-weight:800;margin-bottom:16px">{{ __('Edit Expense Category') }}</h3>
            <form method="POST" action="" id="edit-category-form">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom:12px">
                    <label>{{ __('Name') }} *</label>
                    <input class="input h44" name="name" id="edit-category-name" required maxlength="80">
                </div>
                <div class="field" style="margin-bottom:12px">
                    <label>{{ __('Color') }}</label>
                    <input class="input h44" type="color" name="color" id="edit-category-color" style="padding:4px;height:44px">
                </div>
                <div class="field" style="margin-bottom:16px">
                    <label>{{ __('Description') }}</label>
                    <input class="input h44" name="description" id="edit-category-desc" maxlength="500">
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('edit-category-modal').style.display='none'">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-cta">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCategory(id, name, color, description) {
            document.getElementById('edit-category-form').action = '{{ route('accounting.expenses.categories.update', ':id') }}'.replace(':id', id);
            document.getElementById('edit-category-name').value = name || '';
            document.getElementById('edit-category-color').value = color || '#128F8E';
            document.getElementById('edit-category-desc').value = description || '';
            document.getElementById('edit-category-modal').style.display = 'block';
        }
    </script>
</x-app-layout>
