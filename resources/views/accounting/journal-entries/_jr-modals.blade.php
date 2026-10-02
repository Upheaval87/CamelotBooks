<div class="jr-modal" id="jr-journal" :class="{ on: journalOpen }" @click.self="journalOpen = false">
    <div class="jr-mbox jr-mbox-wide">
        <div class="jr-mbox-h">
            <b>Journal <span x-text="currentEntry()?.no"></span></b>
            <span class="jr-pill" :class="pillClass(currentEntry()?.status)" x-text="'● ' + pillLabel(currentEntry()?.status)"></span>
            <span class="jr-x" @click="journalOpen = false">✕</span>
        </div>

        <div class="jr-mbox-b" x-show="currentEntry()">
            <div class="jr-jmeta">
                <span class="jr-jchip" x-text="currentEntry()?.type"></span>
                <span class="jr-jchip" x-text="currentEntry()?.date"></span>
                <span class="jr-jchip" x-text="currentEntry()?.branch"></span>
                <span class="jr-jchip" x-text="'Source: ' + (currentEntry()?.source || 'manual')"></span>
                <span class="jr-jchip" x-show="currentEntry()?.reference" x-text="'Ref: ' + currentEntry()?.reference"></span>
            </div>

            <div class="jr-jmemo" x-text="currentEntry()?.memo"></div>

            <div class="jr-li-wrap">
                <table class="jr-jlines">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Cost centre</th>
                            <th>Description</th>
                            <th class="num">Debit</th>
                            <th class="num">Credit</th>
                            <th x-show="mode === 'edit'"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, idx) in lines" :key="'jl' + idx">
                            <tr>
                                <td>
                                    <span class="jr-code" x-text="line.a"></span>
                                    <span x-text="line.n"></span>
                                </td>
                                <td class="jr-cc" x-text="line.cc"></td>
                                <td>
                                    <input type="text" class="jr-lin" x-show="mode === 'edit'" x-model="line.d" placeholder="Line memo">
                                    <span x-show="mode !== 'edit'" x-text="line.d || '—'"></span>
                                </td>
                                <td class="num">
                                    <input type="number" step="0.01" class="jr-lin num" x-show="mode === 'edit'" x-model="line.dr">
                                    <span x-show="mode !== 'edit'" x-text="fmt(line.dr)"></span>
                                </td>
                                <td class="num">
                                    <input type="number" step="0.01" class="jr-lin num" x-show="mode === 'edit'" x-model="line.cr">
                                    <span x-show="mode !== 'edit'" x-text="fmt(line.cr)"></span>
                                </td>
                                <td x-show="mode === 'edit'">
                                    <button type="button" class="jr-ib del" title="Remove line" @click="removeLine(idx)">✕</button>
                                </td>
                            </tr>
                        </template>
                        <tr class="tot">
                            <td colspan="3">Totals</td>
                            <td class="num" x-text="fmt(totalDr())"></td>
                            <td class="num" x-text="fmt(totalCr())"></td>
                            <td>
                                <span class="jr-bal" :class="{ bad: !balanced() }"
                                      x-text="balanced() ? '✓ Balanced' : '⚠ Unbalanced'"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="jr-addline" x-show="mode === 'edit'" @click="addLine()">＋ Add line</button>
        </div>

        <div class="jr-mbox-f">
            <span class="jr-note" x-text="footerNote()"></span>

            <div class="jr-mf-acts" x-show="mode === 'view'">
                <button type="button" class="jr-btn jr-btn-g" @click="printEntry(currentId)">🖨 Print</button>

                <template x-if="currentEntry()?.status === 'draft' && isMine(currentEntry()) && can.finalize">
                    <button type="button" class="jr-btn jr-btn-p" @click="startEdit()">✓ Finalize — check / edit &amp; save for posting</button>
                </template>

                <template x-if="(currentEntry()?.status === 'pending_approval' || currentEntry()?.status === 'approved') && can.finalize">
                    <button type="button" class="jr-btn jr-btn-g" @click="openReopen(currentId)">⤺ Reopen</button>
                </template>

                <template x-if="(currentEntry()?.status === 'pending_approval' || currentEntry()?.status === 'approved') && can.post">
                    <button type="button" class="jr-btn jr-btn-p" @click="askPost()">✓ Post to Ledger</button>
                </template>

                <template x-if="currentEntry()?.status === 'posted' && can.reverse">
                    <button type="button" class="jr-btn jr-btn-danger" @click="openReverse(currentId)">⟲ Reverse</button>
                </template>

                <template x-if="currentEntry()?.status === 'reversed'">
                    <button type="button" class="jr-btn jr-btn-g" @click="openReversal(currentId)">⟲ Open reversal</button>
                </template>
            </div>

            <div class="jr-mf-acts" x-show="mode === 'edit'">
                <button type="button" class="jr-btn jr-btn-g" @click="cancelEdit()">Cancel edit</button>
                <button type="button" class="jr-btn jr-btn-p" @click="askFinalize()">✓ Save for Authorisation / Posting (Finalize)</button>
            </div>
        </div>

        <form id="jr-finalize-form" method="POST" style="display:none" :action="currentEntry() ? currentEntry().urls.finalize : ''">
            @csrf
            @foreach($preserved as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <template x-for="(line, idx) in lines" :key="'ff' + idx">
                <span>
                    <input type="hidden" :name="'lines[' + idx + '][account_id]'" :value="line.account_id">
                    <input type="hidden" :name="'lines[' + idx + '][memo]'" :value="line.d">
                    <input type="hidden" :name="'lines[' + idx + '][debit]'" :value="line.dr === null || line.dr === '' ? '' : line.dr">
                    <input type="hidden" :name="'lines[' + idx + '][credit]'" :value="line.cr === null || line.cr === '' ? '' : line.cr">
                </span>
            </template>
        </form>

        <form id="jr-post-form" method="POST" style="display:none" :action="currentEntry() ? currentEntry().urls.post : ''">
            @csrf
            @foreach($preserved as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
    </div>
</div>

<div class="jr-modal" id="jr-delete" :class="{ on: deleteOpen }" @click.self="deleteOpen = false">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b>Delete journal <span x-text="currentEntry()?.no"></span></b>
            <span class="jr-x" @click="deleteOpen = false">✕</span>
        </div>
        <div class="jr-mbox-b">
            <p>This permanently deletes <b x-text="currentEntry()?.no"></b> and all of its lines. Only <b>unfinalized drafts</b> can be deleted, and only by the person who created them. This cannot be undone.</p>
            <form id="jr-delete-form" method="POST" :action="currentEntry() ? currentEntry().urls.destroy : ''">
                @csrf
                @method('DELETE')
                @foreach($preserved as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
            </form>
        </div>
        <div class="jr-mbox-f">
            <span class="jr-note">Deletion is recorded in the audit trail.</span>
            <div class="jr-mf-acts">
                <button type="button" class="jr-btn jr-btn-g" @click="deleteOpen = false">Cancel</button>
                <button type="submit" form="jr-delete-form" class="jr-btn jr-btn-danger">🗑 Delete permanently</button>
            </div>
        </div>
    </div>
</div>

<div class="jr-modal" id="jr-reopen" :class="{ on: reopenOpen }" @click.self="reopenOpen = false">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b>Reopen journal <span x-text="currentEntry()?.no"></span></b>
            <span class="jr-x" @click="reopenOpen = false">✕</span>
        </div>
        <div class="jr-mbox-b">
            <p>Reopening returns <b x-text="currentEntry()?.no"></b> to <b>Unfinalized</b> so its lines can be edited again. The reason is kept in the audit trail.</p>
            <form id="jr-reopen-form" method="POST" :action="currentEntry() ? currentEntry().urls.reopen : ''">
                @csrf
                @foreach($preserved as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label class="jr-lbl">Reason</label>
                <textarea name="reason" class="jr-in" rows="3" maxlength="1000" placeholder="Why is this journal being reopened?"></textarea>
            </form>
        </div>
        <div class="jr-mbox-f">
            <span class="jr-note">Audited: user, IP, timestamp and reason.</span>
            <div class="jr-mf-acts">
                <button type="button" class="jr-btn jr-btn-g" @click="reopenOpen = false">Cancel</button>
                <button type="submit" form="jr-reopen-form" class="jr-btn jr-btn-p">⤺ Reopen as draft</button>
            </div>
        </div>
    </div>
</div>

<div class="jr-modal" id="jr-reverse" :class="{ on: reverseOpen }" @click.self="reverseOpen = false">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b>Reverse journal <span x-text="currentEntry()?.no"></span></b>
            <span class="jr-x" @click="reverseOpen = false">✕</span>
        </div>
        <div class="jr-mbox-b">
            <div class="jr-warn">
                <p>A mirroring journal is created with debits and credits swapped, then posted and cross-linked to <b x-text="currentEntry()?.no"></b>.</p>
            </div>

            {{-- A4.1: mirrored-line preview -- every debit becomes a credit and vice versa --}}
            <div class="jr-mirr" x-show="mirrorLines().length" x-cloak>
                <div class="jr-mirr-h">Reversal preview (lines mirrored)</div>
                <template x-for="(l, i) in mirrorLines()" :key="'mir-' + i">
                    <div class="jr-mline">
                        <span class="jr-mirr-side" :class="l.side === 'DR' ? 'dr' : 'cr'" x-text="l.side"></span>
                        <span class="jr-mirr-code" x-text="l.code"></span>
                        <span class="jr-mirr-name" x-text="l.name"></span>
                        <span class="jr-mirr-amt" x-text="fmt(l.amount)"></span>
                    </div>
                </template>
            </div>

            <form id="jr-reverse-form" method="POST" :action="currentEntry() ? currentEntry().urls.reverse : ''">
                @csrf
                @foreach($preserved as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="jr-g2">
                    <div>
                        <label class="jr-lbl">Reversal date</label>
                        <input type="date" name="reversal_date" class="jr-in" x-model="rv.date" required>
                    </div>
                    <div>
                        <label class="jr-lbl">New reference</label>
                        <input type="text" name="reference" class="jr-in jr-in-mono" maxlength="60" x-model="rv.reference" readonly>
                    </div>
                </div>
                <label class="jr-lbl">Description <span class="jr-req">*</span></label>
                <input type="text" name="memo" class="jr-in" maxlength="1000" required x-model="rv.memo" placeholder="Reason for the reversal (kept in the audit trail)">
                <div class="jr-radios">
                    <label><input type="radio" name="post_mode" value="immediate" x-model="rv.post_mode"> Post immediately</label>
                    <label><input type="radio" name="post_mode" value="draft" x-model="rv.post_mode"> Save as draft</label>
                </div>
            </form>
        </div>
        <div class="jr-mbox-f">
            <span class="jr-note">The original stays on the register as Reversed.</span>
            <div class="jr-mf-acts">
                <button type="button" class="jr-btn jr-btn-g" @click="reverseOpen = false">Cancel</button>
                <button type="button" class="jr-btn jr-btn-danger" @click="askReverse()">⟲ Reverse journal</button>
            </div>
        </div>
    </div>
</div>

<div class="jr-modal jr-cf" id="jr-confirm" :class="{ on: cf.open }" @click.self="cf.open = false">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b x-text="cf.title"></b>
            <span class="jr-x" @click="cf.open = false">✕</span>
        </div>
        <div class="jr-mbox-b">
            <p x-html="cf.msg"></p>
        </div>
        <div class="jr-mbox-f">
            <span class="jr-note">Confirm to continue.</span>
            <div class="jr-mf-acts">
                <button type="button" class="jr-btn jr-btn-g" @click="cf.open = false">Cancel</button>
                <button type="button" class="jr-btn" :class="cf.cls" @click="confirmYes()" x-text="cf.label"></button>
            </div>
        </div>
    </div>
</div>
