<x-app-layout>
    <x-list-header title="{{ __('Record Asset Impairment') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-impairments.store') }}">
                    @csrf

                    <x-form.section number="01" :title="__('Impairment Details')" />

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

                        <div>
                            <x-input-label for="impairment_date" value="{{ __('Impairment Date') }}" />
                            <x-text-input id="impairment_date" name="impairment_date" type="date" class="mt-1 block w-full" :value="old('impairment_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('impairment_date')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="carrying_amount" value="{{ __('Carrying Amount') }}" />
                                <x-text-input id="carrying_amount" name="carrying_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('carrying_amount')" required />
                                <x-input-error :messages="$errors->get('carrying_amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="recoverable_amount" value="{{ __('Recoverable Amount') }}" />
                                <x-text-input id="recoverable_amount" name="recoverable_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('recoverable_amount')" required />
                                <x-input-error :messages="$errors->get('recoverable_amount')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="impairment_loss" value="{{ __('Impairment Loss') }}" />
                            <x-text-input id="impairment_loss" name="impairment_loss" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('impairment_loss')" required />
                            <x-input-error :messages="$errors->get('impairment_loss')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reason" value="{{ __('Reason') }}" />
                            <textarea id="reason" name="reason" rows="3" class="input mt-1">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-impairments.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Record Impairment') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Fixed Assets'), 'route' => route('accounting.fixed-assets.index'), 'icon' => 'grid'],
                        ['title' => __('Asset Impairments List'), 'route' => route('accounting.asset-impairments.index'), 'icon' => 'bars3'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
