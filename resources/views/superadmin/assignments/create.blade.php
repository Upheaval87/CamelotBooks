<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Assign User') }}{{ $preselectUser ? ' — ' . $preselectUser->name : '' }}" description="{{ __('Company, role, and branch scoping for this user.') }}" />

            <form method="POST" action="{{ route('superadmin.assignments.store') }}" id="company-create-form"
                class="mx-auto flex w-full max-w-[1080px] flex-col gap-[22px]"
                x-data="{
                    rows: [{ companyId: '', role: 'viewer', branchOptions: [], branchIds: [] }],
                    addRow() { this.rows.push({ companyId: '', role: 'viewer', branchOptions: [], branchIds: [] }) },
                    removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1) },
                    loadBranches(row) {
                        if (!row.companyId) { row.branchOptions = []; row.branchIds = []; return; }
                        fetch('{{ route('superadmin.companies.branches', ['company' => '__ID__']) }}'.replace('__ID__', row.companyId))
                            .then(r => r.json())
                            .then(data => { row.branchOptions = data; row.branchIds = [] })
                            .catch(() => { row.branchOptions = []; row.branchIds = [] });
                    }
                }">
                @csrf

                <x-form-section icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" title="{{ __('User') }}">
                    <div class="form-field form-field--full">
                        <label class="sa-label" for="user_id">{{ __('Assign assignments to') }}</label>
                        <select id="user_id" name="user_id" class="sa-input" required>
                            <option value="">— {{ __('Select a user') }} —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', $preselectUser?->id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                    </div>
                </x-form-section>

                <template x-for="(row, index) in rows">
                    <x-form-section icon="M3 7v2a2 2 0 002 2h14a2 2 0 002-2V7m0 0a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h4l-1 2h4l-1-2" title-bind="'Company Assignment ' + (index + 1)">
                        <x-slot name="actions">
                            <button type="button"
                                class="sa-remove-row"
                                x-show="index > 0"
                                @click="removeRow(index)"
                                :aria-label="'Remove Company Assignment ' + (index + 1)">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-8 0v12a2 2 0 002 2h6a2 2 0 002-2V7"/>
                                </svg>
                                {{ __('Remove') }}
                            </button>
                        </x-slot>

                        <div class="form-section-grid" style="--sa-cols: 2;">
                            <div class="form-field">
                                <label class="sa-label" :for="'company_' + index">{{ __('Company') }}</label>
                                <select class="sa-input" required
                                    :id="'company_' + index"
                                    :name="'assignments[' + index + '][company_id]'"
                                    x-model="row.companyId"
                                    @change="loadBranches(row)">
                                    <option value="">— {{ __('Select a company') }} —</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}{{ !$company->is_active ? ' (suspended)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="sa-label" :for="'role_' + index">{{ __('Role') }}</label>
                                <select class="sa-input" required
                                    :id="'role_' + index"
                                    :name="'assignments[' + index + '][role]'"
                                    x-model="row.role">
                                    @foreach($roles as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 20px;">
                            <p class="sa-label">{{ __('Branch Access') }}</p>
                            <p style="font-size:0.821rem; color: var(--sa-muted); margin-top: 2px; margin-bottom: 10px;">
                                {{ __('Leave none selected for access to all branches.') }}
                            </p>

                            <template x-if="row.branchOptions.length === 0 && !row.companyId">
                                <p style="font-size:0.893rem; color: #9aa1ae;">{{ __('Select a company to load its branches.') }}</p>
                            </template>
                            <template x-if="row.branchOptions.length === 0 && row.companyId">
                                <p style="font-size:0.893rem; color: #9aa1ae;">{{ __('No branches found for this company.') }}</p>
                            </template>

                            <div class="sa-check-grid" x-show="row.branchOptions.length > 0">
                                <template x-for="branch in row.branchOptions" :key="branch.id">
                                    <label class="sa-check-item">
                                        <input type="checkbox"
                                            :value="branch.id"
                                            :name="'assignments[' + index + '][branch_ids][]'"
                                            x-model="row.branchIds">
                                        <span x-text="branch.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </x-form-section>
                </template>

                <button type="button" class="sa-btn sa-btn--tint" style="margin-top: 24px;" x-data x-on:click="addRow()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Add Another Company Assignment') }}
                </button>

                {{-- Form actions --}}
                <div class="sa-form-actions">
                    <a href="{{ route('superadmin.assignments.index') }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="sa-btn sa-btn--primary">{{ __('Save Assignments') }}</button>
                </div>
            </form>
    </x-superadmin.layout>
</x-app-layout>
