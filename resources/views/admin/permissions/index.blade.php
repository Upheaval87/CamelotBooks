<x-app-layout>
    <x-slot name="header">{{ __('Permission Manager') }}</x-slot>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif

        <div class="card p-6">
            <form method="GET" action="{{ route('admin.permissions.index') }}" class="flex items-end gap-4 mb-6">
                <div>
                    <x-input-label for="role_id">{{ __('Select Role') }}</x-input-label>
                    <select name="role_id" id="role_id" class="input mt-1" onchange="this.form.submit()">
                        <option value="">-- {{ __('Choose a role') }} --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $selectedRole && $selectedRole->id === $role->id ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if($selectedRole)
                @php $hasPermissions = $selectedRole->permissions->pluck('id')->toArray(); @endphp

                <form method="POST" action="{{ route('admin.permissions.sync') }}">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

                    <div class="mb-4 flex items-center gap-4">
                        <x-button type="submit" variant="primary">{{ __('Save Permissions') }}</x-button>
                        <span class="text-sm text-gray-500">
                            {{ __('Editing') }}: <strong>{{ ucfirst(str_replace('_', ' ', $selectedRole->name)) }}</strong>
                        </span>
                    </div>

                    <div x-data="{ tab: 'modules' }">
                        <div class="flex gap-0 mb-6 border-b border-line">
                            <button type="button" @click="tab = 'modules'" :class="tab === 'modules' ? 'border-gold text-ink' : 'border-transparent text-ink-faint hover:text-ink'" class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider border-b-2 transition-colors">
                                {{ __('Module Permissions') }}
                            </button>
                            <button type="button" @click="tab = 'reports'" :class="tab === 'reports' ? 'border-gold text-ink' : 'border-transparent text-ink-faint hover:text-ink'" class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wider border-b-2 transition-colors">
                                {{ __('Report Permissions') }}
                            </button>
                        </div>

                        <div x-show="tab === 'modules'">
                            @foreach($modules as $moduleKey => $actions)
                                @php
                                    $moduleLabel = ucfirst(str_replace(['-', '_'], ' ', $moduleKey));
                                @endphp
                                <div class="mb-4 border border-gray-200 rounded-md overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-2 font-medium text-sm text-ink border-b border-gray-200">
                                        {{ $moduleLabel }}
                                    </div>
                                    <div class="px-4 py-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
                                        @foreach($actions as $action)
                                            @php
                                                $permissionName = "{$moduleKey}.{$action}";
                                                $permission = $allPermissions->get($permissionName);
                                                $isChecked = $permission && in_array($permission->id, $hasPermissions);
                                            @endphp
                                            @if($permission)
                                            <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-gold">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                       {{ $isChecked ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-gold focus:ring-gold">
                                                <span>{{ $action }}</span>
                                            </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div x-show="tab === 'reports'">
                            <div class="border border-gray-200 rounded-md overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2 font-medium text-sm text-ink border-b border-gray-200">
                                    {{ __('Reports') }}
                                </div>
                                <div class="px-4 py-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                    @foreach($reportPermissions as $reportKey => $reportLabel)
                                        @php
                                            $permissionName = "reports.{$reportKey}.view";
                                            $permission = $allPermissions->get($permissionName);
                                            $isChecked = $permission && in_array($permission->id, $hasPermissions);
                                        @endphp
                                        @if($permission)
                                        <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-gold">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-gold focus:ring-gold">
                                            <span>{{ $reportLabel }}</span>
                                        </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-button type="submit" variant="primary">{{ __('Save Permissions') }}</x-button>
                    </div>
                </form>
            @else
                <p class="text-gray-500 text-sm">{{ __('Select a role above to manage its permissions.') }}</p>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
