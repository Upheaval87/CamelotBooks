<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <nav class="crumbs"><a href="{{ route('accounting.accounts.index') }}">Accounts</a> › <span class="here">Structure Setup</span></nav>
            <div style="display:flex;gap:10px">
                <button class="coa-btn coa-btn-ghost coa-btn-sm">Customize</button>
                <button class="coa-btn coa-btn-cta coa-btn-sm">Activate &amp; Lock Format</button>
            </div>
        </div>

        <div class="coa-card" style="margin-bottom:16px">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line);flex-wrap:wrap">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Accounting method</h2>
                <span class="chip" style="margin-left:8px">Inherited · {{ ucfirst($company->accounting_method ?? 'Accrual') }} (from company)</span>
                @if(auth()->user()?->is_super_admin)
                <div style="margin-left:auto"><a href="{{ route('superadmin.companies.edit', $company) }}" class="coa-btn coa-btn-ghost" style="height:30px;padding:0 11px;font-size:11.5px;border-radius:9px">Change at company level</a></div>
                @endif
            </div>
            <div class="coa-pad">
                <div class="warn">ⓘ <span>The method (accrual/cash) is set once at <b>Company Creation</b> and drives which accounts/modules are active. The coding structure below is independent of it.</span></div>
            </div>
        </div>

        <div class="coa-card" x-data="coaSetup()">
            <div class="coa-card coa-pad" style="border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Code structure · segments · generation · activate</h2>
            </div>
            <div class="coa-pad">
                <div class="segrow" style="padding:2px 0 4px">
                    <span class="nm">Default COA</span>
                    <span class="chip" style="margin-left:8px">
                        @if(($company->accounting_method ?? 'accrual') === 'cash')
                            Cash template — AR/AP/inventory inactive
                        @else
                            Accrual template — AR/AP/inventory active
                        @endif
                    </span>
                </div>
                <div class="stepper">
                    <span class="st">1 · Code structure</span>
                    <span class="st">2 · Levels & segments</span>
                    <span class="st">3 · Generation</span>
                    <span class="st">4 · Generate & activate</span>
                </div>

                <div class="optcards" id="sfmt" style="margin-top:12px">
                    <div class="optcard" :class="{ sel: format === 'simple' }" @click="format = 'simple'" data-f="simple">
                        <span class="rd"></span><div class="t">Simple Numeric (4-digit)</div>
                        <div class="ex"><span>1000 Cash</span><span>4000 Sales</span><span>5000 Salaries</span></div>
                    </div>
                    <div class="optcard" :class="{ sel: format === 'num' }" @click="format = 'num'" data-f="num">
                        <span class="rd"></span><div class="t">Hierarchical Numeric</div>
                        <div class="ex"><span>101001 Cash</span><span>501001 Salaries</span></div>
                    </div>
                    <div class="optcard" :class="{ sel: format === 'sep' }" @click="format = 'sep'" data-f="sep">
                        <span class="rd"></span><div class="t">Hierarchical With Separators</div>
                        <div class="ex"><span>1-01-001 Cash</span><span>5-01-001 Salaries</span></div>
                    </div>
                </div>

                <div class="grid2" style="margin-top:16px">
                    <div>
                        <div class="segrow"><span class="nm">Number of levels</span>
                            <select class="coa-in" x-model="levels" @change="updatePreview()" style="width:70px;height:38px;border-radius:10px;border:1px solid var(--border);text-align:center;font-size:13px">
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div id="sseg">
                            <template x-for="(seg, i) in segments" :key="i">
                                <div class="segrow">
                                    <span class="nm" x-text="segLabels[i]"></span>
                                    <input type="number" :value="seg" @input="segments[i] = parseInt($event.target.value) || 1; updatePreview()" style="width:70px;height:38px;border-radius:10px;border:1px solid var(--border);text-align:center;font-size:13px;font-family:inherit">
                                </div>
                            </template>
                        </div>
                        <div class="segrow"><span class="nm">Separator</span>
                            <input type="text" x-model="separator" @input="updatePreview()" value="-" style="width:50px;height:38px;border-radius:10px;border:1px solid var(--border);text-align:center;font-size:13px;font-family:inherit">
                        </div>
                        <div class="result">Result <span class="mono" style="font-size:15px;font-weight:800" x-text="previewResult"></span></div>
                    </div>
                    <div>
                        <div class="optcards" style="grid-template-columns:1fr">
                            <div class="optcard" :class="{ sel: generation === 'auto' }" @click="generation = 'auto'">
                                <span class="rd"></span><div class="t">Automatic (Recommended)</div>
                                <div class="d">System generates next available code.</div>
                            </div>
                            <div class="optcard" :class="{ sel: generation === 'manual' }" @click="generation = 'manual'">
                                <span class="rd"></span><div class="t">Manual (Admin only)</div>
                            </div>
                            <div class="optcard" :class="{ sel: generation === 'hybrid' }" @click="generation = 'hybrid'">
                                <span class="rd"></span><div class="t">Hybrid (suggest + edit)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="coa-pad" style="border-top:1px solid var(--line)">
                    <table class="coa-table" style="min-width:0">
                        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Level</th></tr></thead>
                        <tbody id="scoa">
                            @foreach($previewAccounts as $acct)
                            <tr>
                                <td class="coa-mono" x-text="formatCode('{{ $acct->code }}')">{{ $acct->display_code }}</td>
                                <td>{{ $acct->name }}</td>
                                <td><span class="tchip">{{ ucfirst($acct->type) }}</span></td>
                                <td>{{ $acct->is_group ? 'Group' : 'Posting' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="warn" style="margin-top:12px">⚠ <span><b>Format lock:</b> once transactions exist the format can no longer be changed freely; a controlled migration is required.</span></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function coaSetup() {
        return {
            format: 'sep',
            generation: 'auto',
            levels: 3,
            segments: [1, 2, 3],
            separator: '-',
            segLabels: ['Seg 1 · Class', 'Seg 2 · Group', 'Seg 3 · Detail'],
            previewResult: '5-01-001',
            updatePreview() {
                const parts = [];
                for (let i = 0; i < Math.min(this.levels, this.segments.length); i++) {
                    parts.push(String(this.segments[i]).padStart(this.segments[i], '0'));
                }
                this.previewResult = this.format === 'simple'
                    ? parts.join('')
                    : this.format === 'sep'
                        ? parts.join(this.separator)
                        : parts.join('');
            },
            formatCode(code) {
                if (this.format === 'simple') return code;
                const p = this.segments;
                const c = code[0], g = code.slice(1, 3), d = code.slice(3);
                const parts = [c.slice(0, p[0]), g.slice(0, p[1]), d.slice(0, p[2])];
                return this.format === 'sep' ? parts.join(this.separator) : parts.join('');
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
