<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Edit Assignment') }} — {{ $assignment->user->name }}" description="{{ __('Update role and branch scoping for this assignment.') }}" />

            <form method="POST" action="{{ route('superadmin.assignments.update', $assignment) }}">
                @csrf
                @method('PATCH')

                <x-form-section icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" title="{{ __('Assignment') }}">
                    <div class="form-section-grid" style="--sa-cols: 2;">
                        <div class="form-field">
                            <label class="sa-label">{{ __('User') }}</label>
                            <p style="font-size: 14px; font-weight: 500; color: var(--ink);">{{ $assignment->user->name }}</p>
                        </div>
                        <div class="form-field">
                            <label class="sa-label">{{ __('Company') }}</label>
                            <p style="font-size: 14px; font-weight: 500; color: var(--ink);">{{ $assignment->company->name }}</p>
                        </div>
                    </div>

                    <div class="form-section-grid" style="--sa-cols: 2; margin-top: 20px;">
                        <div class="form-field">
                            <label class="sa-label" for="role">{{ __('Role') }}</label>
                            <select id="role" name="role" class="sa-input" required>
                                @foreach($roles as $code => $label)
                                    <option value="{{ $code }}" @selected(old('role', $assignment->role) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>

                        <div class="form-field">
                            <label class="sa-label" for="is_active">{{ __('Status') }}</label>
                            <select id="is_active" name="is_active" class="sa-input">
                                <option value="1" @selected(old('is_active', $assignment->is_active))>{{ __('Active') }}</option>
                                <option value="0" @selected(!old('is_active', $assignment->is_active))>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <p class="sa-label">{{ __('Branch Access') }}</p>
                        <p style="font-size: 11.5px; color: var(--sa-muted); margin-top: 2px; margin-bottom: 10px;">
                            {{ __('Leave none selected for access to all branches.') }}
                        </p>

                        @if(count($branches))
                            <div class="sa-check-grid">
                                @foreach($branches as $branch)
                                    <label class="sa-check-item">
                                        <input type="checkbox" name="branch_ids[]" value="{{ $branch['id'] }}"
                                            @checked(in_array($branch['id'], old('branch_ids', $assignment->branch_ids ?? [])))>
                                        <span>{{ $branch['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p style="font-size: 12.5px; color: #9aa1ae;">{{ __('No branches found for this company (or it is not provisioned).') }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('branch_ids')" class="mt-1" />
                    </div>
                </x-form-section>

                <div class="sa-form-actions">
                    <a href="{{ route('superadmin.users.show', $assignment->user) }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="sa-btn sa-btn--primary">{{ __('Save Assignment') }}</button>
                </div>
            </form>
    </x-superadmin.layout>

</x-app-layout>
