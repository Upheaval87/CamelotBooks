<x-app-layout>
    <x-slot name="header">{{ __('New Asset Transfer') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-transfers.store') }}">
                    @csrf

                    <x-form.section number="01" :title="__('Transfer Details')" />

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="asset_id" value="{{ __('Fixed Asset') }}" />
                            <x-scoped-search-field
                                name="asset_id"
                                entity="asset"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'asset']) }}"
                                :value="old('asset_id', request('asset_id'))"
                                :label="old('asset_name', ($assets->firstWhere('id', (int) old('asset_id', request('asset_id')))?->name ?? ''))"
                                placeholder="{{ __('Search assets...') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('asset_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="from_branch_id" value="{{ __('From Branch') }}" />
                                <x-scoped-search-field
                                    name="from_branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="old('from_branch_id')"
                                    :label="old('from_branch_id') ? ($branches->firstWhere('id', (int) old('from_branch_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                                <x-input-error :messages="$errors->get('from_branch_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="to_branch_id" value="{{ __('To Branch') }}" />
                                <x-scoped-search-field
                                    name="to_branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="old('to_branch_id')"
                                    :label="old('to_branch_id') ? ($branches->firstWhere('id', (int) old('to_branch_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                                <x-input-error :messages="$errors->get('to_branch_id')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="from_cost_center_id" value="{{ __('From Cost Center') }}" />
                                <x-scoped-search-field
                                    name="from_cost_center_id"
                                    entity="cost-center"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                    :value="old('from_cost_center_id')"
                                    :label="old('from_cost_center_id') ? (($costCenters ?? collect())->firstWhere('id', (int) old('from_cost_center_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                                <x-input-error :messages="$errors->get('from_cost_center_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="to_cost_center_id" value="{{ __('To Cost Center') }}" />
                                <x-scoped-search-field
                                    name="to_cost_center_id"
                                    entity="cost-center"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                    :value="old('to_cost_center_id')"
                                    :label="old('to_cost_center_id') ? (($costCenters ?? collect())->firstWhere('id', (int) old('to_cost_center_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                                <x-input-error :messages="$errors->get('to_cost_center_id')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="transfer_date" value="{{ __('Transfer Date') }}" />
                            <x-text-input id="transfer_date" name="transfer_date" type="date" class="mt-1 block w-full" :value="old('transfer_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('transfer_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" value="{{ __('Reason') }}" />
                            <textarea id="reason" name="reason" rows="3" class="input mt-1">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-transfers.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Transfer') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Fixed Assets'), 'route' => route('accounting.fixed-assets.index'), 'icon' => 'grid'],
                        ['title' => __('Asset Transfers List'), 'route' => route('accounting.asset-transfers.index'), 'icon' => 'bars3'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
