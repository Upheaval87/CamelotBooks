<x-app-layout>
    <x-list-header title="{{ __('Branches') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 card p-6" x-data="branchForm()">
                <div class="flex items-center justify-between mb-4">
                    <div class="form-section-label">1 · Add Branch</div>
                    <div class="text-sm text-gray-600">
                        <template x-if="usage.branch_limit === null">
                            <span>{{ __('Branch usage: unlimited') }}</span>
                        </template>
                        <template x-if="usage.branch_limit !== null">
                            <span x-text="'Branch usage: ' + usage.branch_count + ' of ' + usage.branch_limit + ' used'"></span>
                        </template>
                    </div>
                </div>

                <form method="POST" action="{{ route('branches.store') }}" class="flex flex-wrap items-end gap-4" @submit.prevent="submit()">
                    @csrf
                    <div class="flex-1 min-w-[160px]">
                        <x-input-label for="code" value="{{ __('Code') }}" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                    <div class="flex-1 min-w-[160px]">
                        <x-input-label for="name" value="{{ __('Name') }}" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="flex-1 min-w-[160px]">
                        <x-input-label for="address" value="{{ __('Address') }}" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" />
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                        <label class="flex items-center gap-2 pb-2 text-sm text-gray-600">
                            <input type="checkbox" name="override" value="1" />
                            {{ __('Bypass limit (override)') }}
                        </label>
                    @endif
                    <x-primary-button type="submit" x-bind:disabled="submitting">
                        <span x-text="submitting ? '{{ __('Adding…') }}' : '{{ __('Add') }}'"></span>
                    </x-primary-button>
                    <div x-show="error" x-cloak class="w-full text-sm text-brick" x-text="error"></div>
                </form>

                <div x-show="showUpgrade" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-ink/50" @click="showUpgrade = false"></div>
                    <div class="relative bg-white rounded-xl shadow-elevated max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-ink">{{ __('Branch Limit Reached') }}</h3>
                        <p class="mt-2 text-sm text-gray-600" x-text="errorMessage"></p>
                        <div class="mt-4 flex items-center gap-3">
                            <a href="{{ route('branch-requests.index') }}" class="btn-primary">{{ __('Request More Branches') }}</a>
                            <a href="#" @click.prevent="showUpgrade = false" class="btn-ghost">{{ __('Close') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                                <tr class="{{ $branch->is_active ? '' : 'text-ink-soft' }}">
                                    <td>{{ $branch->code }}</td>
                                    <td>{{ $branch->name }}</td>
                                    <td class="text-ink-soft">{{ $branch->address ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($branch->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill negative">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('branches.toggle', $branch) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $branch->is_active ? 'red' : 'green' }}-600 hover:text-{{ $branch->is_active ? 'red' : 'green' }}-900">
                                                {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-ink-soft text-center">No branches found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function branchForm() {
            return {
                usage: { branch_count: 0, branch_limit: null },
                submitting: false,
                showUpgrade: false,
                error: '',
                errorMessage: '',
                init() {
                    fetch('{{ route('branches.usage') }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(d => { this.usage = d; })
                        .catch(() => {});
                },
                async submit() {
                    const form = this.$el;
                    const data = new FormData(form);
                    this.submitting = true;
                    this.error = '';
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: data,
                        });
                        if (res.ok) {
                            window.location.reload();
                            return;
                        }
                        const body = await res.json();
                        if (body.error_code === 'branch_limit_reached') {
                            this.usage = { branch_count: body.branch_count, branch_limit: body.branch_limit };
                            this.errorMessage = body.message;
                            this.showUpgrade = true;
                        } else if (body.errors) {
                            this.error = Object.values(body.errors).flat().join(' ');
                        } else {
                            this.error = body.message || '{{ __('Unable to create the branch.') }}';
                        }
                    } catch (e) {
                        this.error = '{{ __('Unable to reach the server.') }}';
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
