<x-app-layout>
    <x-slot name="header">{{ __('New Asset Transfer') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-transfers.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="asset_id" value="{{ __('Fixed Asset') }}" />
                            <select id="asset_id" name="asset_id" class="input mt-1" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" {{ old('asset_id', request('asset_id')) == $asset->id ? 'selected' : '' }}>
                                        {{ $asset->asset_code }} - {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asset_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="from_branch_id" value="{{ __('From Branch') }}" />
                                <select id="from_branch_id" name="from_branch_id" class="input mt-1">
                                    <option value="">None</option>
                                    @foreach($branches ?? [] as $branch)
                                        <option value="{{ $branch->id }}" {{ old('from_branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('from_branch_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="to_branch_id" value="{{ __('To Branch') }}" />
                                <select id="to_branch_id" name="to_branch_id" class="input mt-1">
                                    <option value="">None</option>
                                    @foreach($branches ?? [] as $branch)
                                        <option value="{{ $branch->id }}" {{ old('to_branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('to_branch_id')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="from_cost_center_id" value="{{ __('From Cost Center') }}" />
                                <select id="from_cost_center_id" name="from_cost_center_id" class="input mt-1">
                                    <option value="">None</option>
                                    @foreach($costCenters ?? [] as $cc)
                                        <option value="{{ $cc->id }}" {{ old('from_cost_center_id') == $cc->id ? 'selected' : '' }}>
                                            {{ $cc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('from_cost_center_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="to_cost_center_id" value="{{ __('To Cost Center') }}" />
                                <select id="to_cost_center_id" name="to_cost_center_id" class="input mt-1">
                                    <option value="">None</option>
                                    @foreach($costCenters ?? [] as $cc)
                                        <option value="{{ $cc->id }}" {{ old('to_cost_center_id') == $cc->id ? 'selected' : '' }}>
                                            {{ $cc->name }}
                                        </option>
                                    @endforeach
                                </select>
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
                            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-transfers.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Transfer') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
