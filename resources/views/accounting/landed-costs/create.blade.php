<x-app-layout>
    <x-list-header title="{{ __('Create Landed Cost Voucher') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('accounting.landed-costs.store') }}">
                        @csrf

                        <div class="card p-6 mb-6">
                            <x-form.section number="01" :title="__('General Information')" />
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                                    <x-scoped-search-field
                                        name="vendor_id"
                                        entity="vendor"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                        :value="old('vendor_id')"
                                        :label="old('vendor_name', ($vendors->firstWhere('id', (int) old('vendor_id'))?->name ?? ''))"
                                        placeholder="{{ __('Search vendors...') }}"
                                        required
                                    />
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
                            <x-form.section number="02" :title="__('Link GRNs')" />
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

                        <div class="card p-6 mb-6" id="components-card">
                            <div class="flex items-center justify-between mb-4">
                                <x-form.section number="03" :title="__('Cost Components')" />
                                <button type="button" onclick="addComponent()" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Component</button>
                            </div>
                            <div id="components-wrap" class="space-y-3">
                                @php $oldComponents = old('components', [['component_type' => 'freight', 'description' => '', 'amount' => '', 'payee_account_id' => '']]); @endphp
                                @foreach($oldComponents as $compIdx => $comp)
                                    <div class="component-row grid grid-cols-4 gap-3 p-3 border rounded bg-gray-50">
                                        <div>
                                            <label class="block text-xs text-gray-500">Type</label>
                                            <select name="components[{{ $compIdx }}][component_type]" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                                <option value="freight" {{ ($comp['component_type'] ?? '') === 'freight' ? 'selected' : '' }}>Freight</option>
                                                <option value="customs" {{ ($comp['component_type'] ?? '') === 'customs' ? 'selected' : '' }}>Customs</option>
                                                <option value="insurance" {{ ($comp['component_type'] ?? '') === 'insurance' ? 'selected' : '' }}>Insurance</option>
                                                <option value="handling" {{ ($comp['component_type'] ?? '') === 'handling' ? 'selected' : '' }}>Handling</option>
                                                <option value="other" {{ ($comp['component_type'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Description</label>
                                            <input name="components[{{ $compIdx }}][description]" value="{{ $comp['description'] ?? '' }}" type="text" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="e.g. Ocean freight">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Amount</label>
                                            <input name="components[{{ $compIdx }}][amount]" value="{{ $comp['amount'] ?? '' }}" type="number" step="0.01" min="0.01" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Payee Account</label>
                                            <x-scoped-search-field
                                                name="components[{{ $compIdx }}][payee_account_id]"
                                                entity="account"
                                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                                :value="$comp['payee_account_id'] ?? ''"
                                                :label="($comp['payee_account_id'] ?? '') ? (($accounts->firstWhere('id', (int) $comp['payee_account_id'])) ? $accounts->firstWhere('id', (int) $comp['payee_account_id'])->code . ' - ' . $accounts->firstWhere('id', (int) $comp['payee_account_id'])->name : '') : ''"
                                                placeholder="Select"
                                                required
                                            />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('components')" class="mt-2" />
                        </div>

                        <div class="flex justify-end mt-8 gap-3">
                            <x-button variant="ghost" href="{{ route('accounting.landed-costs.index') }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Create Landed Cost Voucher') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
        let componentIndex = {{ count($oldComponents) }};

        function componentRowHtml(idx) {
            return `
                <div class="component-row grid grid-cols-4 gap-3 p-3 border rounded bg-gray-50">
                    <div>
                        <label class="block text-xs text-gray-500">Type</label>
                        <select name="components[${idx}][component_type]" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="freight">Freight</option>
                            <option value="customs">Customs</option>
                            <option value="insurance">Insurance</option>
                            <option value="handling">Handling</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Description</label>
                        <input name="components[${idx}][description]" type="text" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" placeholder="e.g. Ocean freight">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Amount</label>
                        <input name="components[${idx}][amount]" type="number" step="0.01" min="0.01" required class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Payee Account</label>
                        ${scopedSearchFieldHtml({
                            name: 'components[${idx}][payee_account_id]',
                            entity: 'account',
                            searchUrl: ACCOUNT_SEARCH_URL,
                            value: '',
                            label: '',
                            placeholder: 'Select',
                            required: true,
                        })}
                    </div>
                </div>`;
        }

        function addComponent() {
            document.getElementById('components-wrap').insertAdjacentHTML('beforeend', componentRowHtml(componentIndex++));
        }
    </script>
</x-app-layout>
