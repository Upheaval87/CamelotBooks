<x-app-layout>
    <x-slot name="header">{{ __('New Assignment') }}</x-slot>

    @include('superadmin._nav', ['active' => 'assignments'])

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.assignments.store') }}"
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

                    <div class="max-w-md">
                        <x-input-label for="user_id">{{ __('User') }}</x-input-label>
                        <select id="user_id" name="user_id" class="input mt-1 block w-full" required>
                            <option value="">— {{ __('Select a user') }} —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', $preselectUser?->id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                    </div>

                    <div class="mt-8 space-y-4">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="rounded-lg border border-line p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs uppercase tracking-wide text-gray-500 font-semibold" x-text="'Company Assignment ' + (index + 1)"></span>
                                    <button type="button" @click="removeRow(index)" x-show="rows.length > 1" class="text-sm text-brick hover:underline">Remove</button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="input-label" :for="'company_' + index">{{ __('Company') }}</label>
                                        <select class="input mt-1 block w-full" required
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

                                    <div>
                                        <label class="input-label" :for="'role_' + index">{{ __('Role') }}</label>
                                        <select class="input mt-1 block w-full" required
                                            :id="'role_' + index"
                                            :name="'assignments[' + index + '][role]'"
                                            x-model="row.role">
                                            @foreach($roles as $code => $label)
                                                <option value="{{ $code }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <p class="input-label">{{ __('Branch Access') }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Leave none selected for access to all branches.') }}</p>

                                    <template x-if="row.branchOptions.length === 0 && !row.companyId">
                                        <p class="text-sm text-gray-400 mt-2">{{ __('Select a company to load its branches.') }}</p>
                                    </template>
                                    <template x-if="row.branchOptions.length === 0 && row.companyId">
                                        <p class="text-sm text-gray-400 mt-2">{{ __('No branches found for this company.') }}</p>
                                    </template>

                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 mt-2" x-show="row.branchOptions.length > 0">
                                        <template x-for="branch in row.branchOptions" :key="branch.id">
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox"
                                                    :value="branch.id"
                                                    :name="'assignments[' + index + '][branch_ids][]'"
                                                    x-model="row.branchIds"
                                                    class="rounded border-line text-accent focus:ring-accent">
                                                <span x-text="branch.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4">
                        <x-button variant="ghost" type="button" x-data x-on:click="addRow()">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add Another Company Assignment') }}
                        </x-button>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Save Assignments') }}</x-button>
                        <a href="{{ route('superadmin.assignments.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
