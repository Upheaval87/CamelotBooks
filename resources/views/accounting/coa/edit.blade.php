<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <nav class="crumbs"><a href="{{ route('accounting.coa.index') }}">Accounts</a> › <span class="here">{{ $account->display_code }}</span> › <span class="here">Edit</span></nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.coa.index') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Cancel</a>
                <button type="submit" form="coa-edit-form" class="coa-btn coa-btn-cta coa-btn-sm">Save Changes</button>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-pad" x-data="coaEditForm()">
                <form id="coa-edit-form" method="POST" action="{{ route('accounting.coa.update', $account) }}">
                    @csrf
                    @method('PUT')
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px 26px">
                        <div class="f">
                            <label>Account Number (display)</label>
                            <input class="coa-in mono" name="code" x-model="code" @input="validateCode()" placeholder="X-XX-XXX" maxlength="6">
                            <div class="msg" :class="msgClass" x-text="validationMsg"></div>
                        </div>

                        <div class="f">
                            <label>Account Name <span class="req" style="color:var(--red-2)">*</span></label>
                            <input class="coa-in" name="name" value="{{ $account->name }}" required>
                        </div>

                        <div class="f">
                            <label>Account Type (Class)</label>
                            <select class="coa-in" name="type" x-model="accountType" @change="filterParents()">
                                @foreach(['asset','liability','equity','income','expense'] as $t)
                                <option value="{{ $t }}" {{ $account->type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}s</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="f">
                            <label>Parent (Category)</label>
                            <select class="coa-in" name="parent_id" x-model="parentId">
                                <option value="">— None —</option>
                                <template x-for="p in filteredParents" :key="p.id">
                                    <option :value="p.id" x-text="p.display_code + ' ' + p.name" :selected="p.id == parentId"></option>
                                </template>
                            </select>
                        </div>

                        <div class="f">
                            <label>Posting Behaviour</label>
                            <select class="coa-in" name="posting_behaviour">
                                <option value="both" {{ $account->posting_behaviour === 'both' ? 'selected' : '' }}>Both (Mixed)</option>
                                <option value="debit_only" {{ $account->posting_behaviour === 'debit_only' ? 'selected' : '' }}>Debit only</option>
                                <option value="credit_only" {{ $account->posting_behaviour === 'credit_only' ? 'selected' : '' }}>Credit only</option>
                            </select>
                        </div>

                        <div class="f">
                            <label>Normal Balance</label>
                            <select class="coa-in" name="normal_balance">
                                <option value="">Auto-detect from type</option>
                                <option value="debit" {{ ($account->normal_balance ?? '') === 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ ($account->normal_balance ?? '') === 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>

                        <div class="f">
                            <label>Description</label>
                            <textarea class="coa-in" name="description" rows="2" style="height:auto;border-radius:14px;padding:12px 16px;resize:vertical">{{ $account->description }}</textarea>
                        </div>
                    </div>

                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--line)">
                        <label class="tog"><span class="sw {{ $account->is_active ? 'on' : '' }}" @click="$el.classList.toggle('on')"></span>Active</label>
                        <label class="tog"><span class="sw {{ $account->allow_adjustments ? 'on' : '' }}" @click="$el.classList.toggle('on')"></span>Allow journal adjustments
                            <span style="font-size:10.5px;color:var(--faint);font-weight:600">Off = manual adjustment journals blocked. <b>Bank reconciliation postings always allowed.</b></span>
                        </label>
                        <label class="tog"><span class="sw {{ $account->is_system_account ? 'on' : '' }}" @click="$el.classList.toggle('on')"></span>System account</label>
                    </div>
                </form>

                <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:12px;font-size:11.5px;color:var(--faint)">
                    Try <b style="font-family:ui-monospace,Menlo,monospace;color:var(--ink)">{{ $existingAccount?->display_code ?? '—' }}</b> (exists → blocked),
                    or <b style="font-family:ui-monospace,Menlo,monospace;color:var(--ink)">{{ $availableCode ?? '—' }}</b> (available).
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function coaEditForm() {
        const currentStored = '{{ $account->code }}';
        return {
            code: @js($account->display_code),
            accountType: @js($account->type),
            parentId: @js((string) $account->parent_id),
            validationMsg: '✓ {{ $account->display_code }} is the current code (stored ' + currentStored + ').',
            msgClass: 'ok',
            allAccounts: {!! json_encode($accounts->map(fn($a) => ['id'=>$a->id,'code'=>$a->code,'display_code'=>$a->display_code,'name'=>$a->name,'type'=>$a->type])->values()) !!},
            filteredParents: {!! json_encode($parentAccounts->map(fn($a) => ['id'=>$a->id,'code'=>$a->code,'display_code'=>$a->display_code,'name'=>$a->name,'type'=>$a->type])->values()) !!},
            validateCode() {
                const stored = this.code.replace(/-/g, '');
                if (!stored) { this.validationMsg = 'Enter a code in X-XX-XXX format.'; this.msgClass = 'neutral'; return; }
                if (!/^\d{6}$/.test(stored)) { this.validationMsg = '✗ Must be 6 digits (X-XX-XXX).'; this.msgClass = 'err'; return; }
                if (stored === currentStored) { this.validationMsg = '✓ ' + this.toDashed(stored) + ' is the current code (stored ' + stored + ').'; this.msgClass = 'ok'; return; }
                const exists = this.allAccounts.find(a => a.code === stored);
                if (exists) { this.validationMsg = '✗ ' + this.toDashed(stored) + ' already exists — ' + exists.name + '.'; this.msgClass = 'err'; return; }
                this.validationMsg = '✓ ' + this.toDashed(stored) + ' is available (stored ' + stored + ').';
                this.msgClass = 'ok';
            },
            toDashed(s) { return s[0] + '-' + s.slice(1,3) + '-' + s.slice(3); },
            filterParents() {
                this.filteredParents = this.allAccounts.filter(a => a.type === this.accountType && a.is_group);
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
