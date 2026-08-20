<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <nav class="crumbs"><a href="{{ route('accounting.coa.index') }}">Accounts</a> › <span class="here">New Account</span></nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.coa.index') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Cancel</a>
                <button type="submit" form="coa-create-form" name="action" value="save_and_new" class="coa-btn coa-btn-ghost coa-btn-sm">Save & New</button>
                <button type="submit" form="coa-create-form" class="coa-btn coa-btn-cta coa-btn-sm">Save Account</button>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-pad" x-data="coaCreateForm()">
                <form id="coa-create-form" method="POST" action="{{ route('accounting.coa.store') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px 26px">
                        <div class="f" style="grid-column:1/-1">
                            <label>Account Code Creation Mode</label>
                            <div class="segc">
                                <button type="button" :class="{ on: mode === 'auto' }" @click="mode = 'auto'">Auto Generate (Recommended)</button>
                                <button type="button" :class="{ on: mode === 'manual' }" @click="mode = 'manual'">Manual Entry (Admin only)</button>
                            </div>
                            <div class="msg neutral">Format X-XX-XXX · stored dash-less · L1/L2 groups non-posting · L3 posting.</div>
                        </div>

                        <div class="f">
                            <label>Account Number</label>
                            <input class="coa-in mono" name="code" x-model="code" :disabled="mode === 'auto'" placeholder="X-XX-XXX" maxlength="6">
                            @error('code') <div class="msg err">{{ $message }}</div> @enderror
                            <template x-if="mode === 'auto' && codeMsg">
                                <div class="msg" :class="codeMsg.startsWith('✓') ? 'ok' : 'err'" x-text="codeMsg"></div>
                            </template>
                        </div>

                        <div class="f">
                            <label>Account Type (Class) <span class="req" style="color:var(--red-2)">*</span></label>
                            <select class="coa-in" name="type" x-model="accountType" @change="filterParents()">
                                <option value="asset">1 · Assets</option>
                                <option value="liability">2 · Liabilities</option>
                                <option value="equity">3 · Equity</option>
                                <option value="income">4 · Income</option>
                                <option value="expense">5 · Expenses</option>
                            </select>
                        </div>

                        <div class="f">
                            <label>Parent (Category) <span class="req" style="color:var(--red-2)">*</span></label>
                            <select class="coa-in" name="parent_id" x-model="parentId" @change="onParentChange()">
                                <option value="">— None (top-level group) —</option>
                                <template x-for="p in filteredParents" :key="p.id">
                                    <option :value="p.id" x-text="p.display_code + ' ' + p.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="f">
                            <label>Account Name <span class="req" style="color:var(--red-2)">*</span></label>
                            <input class="coa-in" name="name" x-model="name" required>
                        </div>

                        <div class="f">
                            <label>Account Level</label>
                            <input class="coa-in" :value="'Level ' + computedLevel + (computedLevel === 3 ? ' · Posting' : ' · Group')" disabled>
                        </div>

                        <div class="f">
                            <label>Normal Balance</label>
                            <select class="coa-in" name="normal_balance">
                                <option value="">Auto-detect from type</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>

                        <div class="f">
                            <label>Posting Behaviour</label>
                            <select class="coa-in" name="posting_behaviour">
                                <option value="both">Both (Mixed)</option>
                                <option value="debit_only">Debit only</option>
                                <option value="credit_only">Credit only</option>
                            </select>
                        </div>

                        <div class="f">
                            <label>Currency</label>
                            <input class="coa-in" name="currency" value="{{ $defaultCurrency ?? 'MWK' }}" readonly>
                        </div>

                        <div class="f" style="grid-column:1/-1">
                            <label>Description</label>
                            <textarea class="coa-in" name="description" rows="2" style="height:auto;border-radius:14px;padding:12px 16px;resize:vertical"></textarea>
                        </div>
                    </div>

                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--line)">
                        <label class="tog"><span class="sw on" @click="$el.classList.toggle('on')"></span>Tax applicable</label>
                        <label class="tog"><span class="sw on" @click="$el.classList.toggle('on')"></span>Reconciliation required</label>
                        <label class="tog"><span class="sw on" @click="$el.classList.toggle('on')"></span>Allow journal adjustments
                            <span style="font-size:10.5px;color:var(--faint);font-weight:600">Off = manual adjustment journals blocked. <b>Bank reconciliation postings always allowed.</b></span>
                        </label>
                        <label class="tog"><span class="sw" @click="$el.classList.toggle('on')"></span>Cost centre required</label>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function coaCreateForm() {
        return {
            mode: 'auto',
            code: '',
            codeMsg: '',
            accountType: 'asset',
            parentId: '',
            name: '',
            filteredParents: {!! json_encode($parentAccounts->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'display_code' => $a->display_code, 'name' => $a->name, 'type' => $a->type])->values()) !!},
            existingCodes: {!! json_encode($accounts->pluck('code')->values()) !!},
            computedLevel: 1,
            filterParents() {
                this.filteredParents = this.parentAccounts.filter(p => p.type === this.accountType);
                this.onParentChange();
            },
            get parentAccounts() {
                return {!! json_encode($parentAccounts->map(fn($a) => ['id' => $a->id, 'code' => $a->code, 'display_code' => $a->display_code, 'name' => $a->name, 'type' => $a->type])->values()) !!};
            },
            onParentChange() {
                this.computedLevel = this.parentId ? 2 : 1;
                if (this.mode === 'auto') this.generateCode();
            },
            generateCode() {
                const typeMap = { asset:'1', liability:'2', equity:'3', income:'4', expense:'5' };
                const cls = typeMap[this.accountType] || '1';
                const parent = this.parentAccounts.find(p => String(p.id) === String(this.parentId));
                const grp = parent ? parent.code.slice(1, 3) : '00';
                const prefix = cls + grp;
                let max = 0;
                this.existingCodes.forEach(c => {
                    if (c.startsWith(prefix) && parseInt(c) > max) max = parseInt(c);
                });
                const next = max ? max + 1 : parseInt(prefix + '001');
                this.code = String(next).padStart(6, '0');
                this.codeMsg = '✓ Auto-generated · stored ' + this.code + ' · displays ' + this.toDashed(this.code) + ' · Level 3 posting.';
            },
            toDashed(stored) {
                return stored[0] + '-' + stored.slice(1, 3) + '-' + stored.slice(3);
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
