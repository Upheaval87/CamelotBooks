<x-app-layout>
    <x-slot name="header">{{ __('Create Landed Cost Voucher') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.landed-costs.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.landed-costs.store') }}">
                @csrf

                <div class="card p-6 mb-6">
                    <div class="form-section-label">1 · GENERAL INFORMATION</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <select id="vendor_id" name="vendor_id" class="input mt-1" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="allocation_method" value="{{ __('Allocation Method') }}" />
                            <select id="allocation_method" name="allocation_method" class="input mt-1" required>
                                <option value="by_value" {{ old('allocation_method') === 'by_value' ? 'selected' : '' }}>By Invoice Value</option>
                                <option value="by_quantity" {{ old('allocation_method') === 'by_quantity' ? 'selected' : '' }}>By Quantity</option>
                            </select>
                            <x-input-error :messages="$errors->get('allocation_method')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="{{ __('Notes') }}" />
                            <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes')" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="form-section-label">2 · LINK GRNS</div>
                    <p class="text-sm text-gray-500 mb-3">Select the Goods Received Notes this landed cost applies to.</p>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @forelse($grns as $grn)
                            <label class="flex items-center p-2 border rounded hover:bg-gray-50">
                                <input type="checkbox" name="grn_ids[]" value="{{ $grn->id }}" {{ in_array($grn->id, old('grn_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-3 text-sm">
                                    <span class="font-medium">{{ $grn->grn_number }}</span> &mdash; {{ $grn->date->format('M d, Y') }} &mdash; {{ format_money($grn->lines->sum('total_cost')) }} &mdash; {{ $grn->lines->count() }} line(s)
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No posted GRNs found.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('grn_ids')" class="mt-2" />
                </div>

                <div class="card p-6 mb-6" x-data="{
                    components: {{ Js::from(old('components', [['component_type' => 'freight', 'description' => '', 'amount' => '', 'payee_account_id' => '']])) }}
                }">
                    <div class="flex items-center justify-between mb-4">
                        <div class="form-section-label">3 · COST COMPONENTS</div>
                        <button type="button" @click="components.push({ component_type: 'freight', description: '', amount: '', payee_account_id: '' })" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Component</button>
                    </div>
                    <template x-for="(comp, index) in components" :key="index">
                        <div class="grid grid-cols-4 gap-3 mb-3 p-3 border rounded bg-gray-50">
                            <div>
                                <label class="block text-xs text-gray-500">Type</label>
                                <select :name="'components['+index+'][component_type]'" x-model="comp.component_type" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                    <option value="freight">Freight</option><option value="customs">Customs</option><option value="insurance">Insurance</option><option value="handling">Handling</option><option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Description</label>
                                <input :name="'components['+index+'][description]'" x-model="comp.description" type="text" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="e.g. Ocean freight">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Amount</label>
                                <input :name="'components['+index+'][amount]'" x-model.number="comp.amount" type="number" step="0.01" min="0.01" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Payee Account</label>
                                <select :name="'components['+index+'][payee_account_id]'" x-model="comp.payee_account_id" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                    <option value="">Select</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </template>
                    <x-input-error :messages="$errors->get('components')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <x-button variant="ghost" href="{{ route('accounting.landed-costs.index') }}">{{ __('Cancel') }}</x-button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Create Landed Cost Voucher</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
