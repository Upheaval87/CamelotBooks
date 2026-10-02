{{--
    JOURNALS REGISTER — per-journal modals.

    §2.7 / §7.2: every modal is SERVER-RENDERED. The register hands this partial
    the same `$payload` Alpine receives, so the DOM and the JS state can never
    disagree. JS only orchestrates: open/close, edit-mode toggling and the
    balance recalculation.

    Expects: $entries (payload array), $can, $preserved, $decimals, $cs
--}}
@php
    $money = fn ($value) => format_number((float) $value, (int) $decimals);

    $pillMap = [
        'draft' => ['unfin', 'Unfinalized'],
        'pending_approval' => ['unpost', 'Unposted'],
        'approved' => ['unpost', 'Unposted'],
        'posted' => ['posted', 'Posted'],
        'reversed' => ['reversed', 'Reversed'],
    ];
@endphp

@foreach($entries as $entry)
    @php
        $id = (int) $entry['id'];
        $status = (string) $entry['status'];
        [$pillClass, $pillLabel] = $pillMap[$status] ?? ['reversed', $status];

        $isMine = (int) ($entry['creator'] ?? 0) === (int) auth()->id();
        $isDraft = $status === 'draft';
        $isUnposted = in_array($status, ['pending_approval', 'approved'], true);
        $isPosted = $status === 'posted';
        $isReversed = $status === 'reversed';

        $canFinalize = (bool) ($can['finalize'] ?? false);
        $canPost = (bool) ($can['post'] ?? false);
        $canReverse = (bool) ($can['reverse'] ?? false);
        $canDelete = (bool) ($can['delete'] ?? false);

        $editable = $isDraft && $isMine && $canFinalize;
        $deletable = $isDraft && $isMine && $canDelete;

        $note = match (true) {
            $isDraft && $isMine => 'Unfinalized draft — you are the creator, so you can edit, finalize or delete it.',
            $isDraft => 'Unfinalized draft — only the creator (' . ($entry['creatorName'] ?? '—') . ') can edit, finalize or delete it.',
            $isUnposted => 'Finalized and awaiting a second person to post it to the ledger.',
            $isPosted => 'Posted to the ledger — immutable. Use Reverse to correct it.',
            $isReversed => 'Reversed — this entry is void.' . (! empty($entry['reversalNo']) ? ' Offsetting journal: ' . $entry['reversalNo'] . '.' : ''),
            default => '',
        };
    @endphp

    {{-- ── journal modal ────────────────────────────────────────────────── --}}
    <div class="jr-modal" id="jr-journal-{{ $id }}"
         :class="{ on: currentId === {{ $id }} }"
         @click.self="closeJournal()">
        <div class="jr-mbox jr-mbox--journal">
            <div class="jr-mbox-h">
                <b>Journal {{ $entry['no'] }}</b>
                <span class="jr-pill {{ $pillClass }}">● {{ $pillLabel }}</span>
                <span class="jr-x" @click="closeJournal()" role="button" aria-label="Close">✕</span>
            </div>

            <div class="jr-mbox-b">
                <div class="jr-jmeta">
                    <span class="jr-jchip">{{ $entry['type'] }}</span>
                    <span class="jr-jchip">{{ $entry['date'] }}</span>
                    <span class="jr-jchip">{{ $entry['branch'] }}</span>
                    <span class="jr-jchip">Source: {{ $entry['source'] ?: 'manual' }}</span>
                    @if(! empty($entry['reference']))
                        <span class="jr-jchip">Ref: {{ $entry['reference'] }}</span>
                    @endif
                    <span class="jr-jchip">Creator: {{ $entry['creatorName'] ?? '—' }}</span>
                </div>

                <div class="jr-jmemo">{{ $entry['memo'] ?: '—' }}</div>

                {{-- view mode: server-rendered ledger lines --}}
                <div class="jr-li-wrap" x-show="mode === 'view'">
                    <table class="jr-jlines">
                        <thead>
                            <tr>
                                <th style="width:32%">Account</th>
                                <th>Description</th>
                                <th style="width:12%">Cost centre</th>
                                <th class="num" style="width:13%">Debit</th>
                                <th class="num" style="width:13%">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry['lines'] as $line)
                                <tr>
                                    <td><span class="jr-code">{{ $line['a'] }}</span>{{ $line['n'] }}</td>
                                    <td>{{ $line['d'] ?: '—' }}</td>
                                    <td class="jr-cc">{{ $line['cc'] }}</td>
                                    <td class="num">{{ $line['dr'] !== null ? $money($line['dr']) : '—' }}</td>
                                    <td class="num">{{ $line['cr'] !== null ? $money($line['cr']) : '—' }}</td>
                                </tr>
                            @endforeach
                            <tr class="tot">
                                <td colspan="3">Totals</td>
                                <td class="num">{{ $money($entry['totalDebit'] ?? array_sum(array_map(fn ($l) => $l['dr'] ?? 0, $entry['lines']))) }}</td>
                                <td class="num">{{ $money($entry['totalCredit'] ?? array_sum(array_map(fn ($l) => $l['cr'] ?? 0, $entry['lines']))) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- edit mode: server-rendered editable lines inside the finalize form --}}
                <template x-if="currentId === {{ $id }} && mode === 'edit'">
                    <div>
                        <form id="jr-finalize-{{ $id }}" method="POST" action="{{ route('accounting.journal-entries.finalize', $id) }}">
                            @csrf
                            @foreach($preserved as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <div class="jr-li-wrap">
                                <table class="jr-jlines">
                                    <thead>
                                        <tr>
                                            <th style="width:32%">Account</th>
                                            <th>Description</th>
                                            <th style="width:12%">Cost centre</th>
                                            <th class="num" style="width:13%">Debit</th>
                                            <th class="num" style="width:13%">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entry['lines'] as $i => $line)
                                            <tr>
                                                <td>
                                                    <span class="jr-code">{{ $line['a'] }}</span>{{ $line['n'] }}
                                                    <input type="hidden" name="lines[{{ $i }}][account_id]" value="{{ $line['account_id'] }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="jr-lin" name="lines[{{ $i }}][memo]"
                                                           x-model="lines[{{ $i }}].d" maxlength="500"
                                                           placeholder="Line memo">
                                                </td>
                                                <td class="jr-cc">{{ $line['cc'] }}</td>
                                                <td class="num">
                                                    <input type="number" step="0.01" min="0"
                                                           class="jr-lin num" name="lines[{{ $i }}][debit]"
                                                           x-model="lines[{{ $i }}].dr">
                                                </td>
                                                <td class="num">
                                                    <input type="number" step="0.01" min="0"
                                                           class="jr-lin num" name="lines[{{ $i }}][credit]"
                                                           x-model="lines[{{ $i }}].cr">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="tot">
                                            <td colspan="3">Totals</td>
                                            <td class="num" x-text="fmt(totalDr())"></td>
                                            <td class="num" x-text="fmt(totalCr())"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </template>
            </div>

            <div class="jr-mbox-f">
                <span class="jr-note">{{ $note }}</span>

                <div class="jr-mf-acts" x-show="mode === 'view'">
                    @if($isPosted)
                        @if($canReverse)
                            <button type="button" class="jr-btn jr-btn-g" @click="openPrint({{ $id }})">🖨 Print</button>
                            <button type="button" class="jr-btn jr-btn-danger" @click="openReverse({{ $id }})">⟲ Reverse</button>
                        @endif
                    @elseif($isUnposted)
                        @if($canFinalize)
                            <button type="button" class="jr-btn jr-btn-g" @click="openReopen({{ $id }})">⤺ Reopen as draft</button>
                        @endif
                        @if($canPost)
                            <button type="button" class="jr-btn jr-btn-p" @click="askPost()">▶ Post to ledger</button>
                        @endif
                    @elseif($editable)
                        <button type="button" class="jr-btn jr-btn-danger" @click="openDelete({{ $id }})">🗑 Delete draft</button>
                        <button type="button" class="jr-btn jr-btn-p" @click="startEdit()">✎ Check &amp; Edit</button>
                    @endif
                </div>

                <div class="jr-mf-acts" x-show="mode === 'edit'">
                    <span class="jr-bal" :class="{ bad: !balanced() }"
                          x-text="balanced() ? '✓ Balanced' : '⚠ Unbalanced'"></span>
                    <button type="button" class="jr-btn jr-btn-g" @click="cancelEdit()">Cancel edit</button>
                    <button type="button" class="jr-btn jr-btn-p"
                            :disabled="!balanced()"
                            @click="askFinalize()">✓ Save for Authorisation / Posting (Finalize)</button>
                </div>
            </div>

            <form id="jr-post-{{ $id }}" method="POST" action="{{ route('accounting.journal-entries.post', $id) }}" style="display:none">
                @csrf
                @foreach($preserved as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
            </form>
        </div>
    </div>

    {{-- ── reversal modal (posted entries only; server-rendered mirror preview) ── --}}
    @if($isPosted)
        <div class="jr-modal jr-modal--rev" id="jr-rev-{{ $id }}"
             :class="{ on: reverseId === {{ $id }} }"
             @click.self="closeReverse()">
            <div class="jr-mbox">
                <div class="jr-mbox-h">
                    <b>Reverse journal {{ $entry['no'] }}</b>
                    <span class="jr-x" @click="closeReverse()" role="button" aria-label="Close">✕</span>
                </div>
                <div class="jr-mbox-b">
                    <div class="jr-warn">
                        <p>A mirroring journal is created with debits and credits swapped, then posted and cross-linked to <b>{{ $entry['no'] }}</b>.</p>
                    </div>

                    <div class="jr-mirr">
                        <div class="jr-mirr-h">Reversal preview (lines mirrored)</div>
                        @foreach($entry['lines'] as $line)
                            @php
                                $mirrorSide = $line['dr'] !== null ? 'CR' : 'DR';
                                $mirrorAmount = $line['dr'] !== null ? $line['dr'] : $line['cr'];
                            @endphp
                            <div class="jr-mline">
                                <span class="jr-mirr-side {{ strtolower($mirrorSide) }}">{{ $mirrorSide }}</span>
                                <span class="jr-mirr-code">{{ $line['a'] }}</span>
                                <span class="jr-mirr-name">{{ $line['n'] }}</span>
                                <span class="jr-mirr-amt">{{ $money($mirrorAmount) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <form id="jr-reverse-{{ $id }}" method="POST" action="{{ route('accounting.journal-entries.reverse', $id) }}">
                        @csrf
                        @foreach($preserved as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div class="jr-g2">
                            <div>
                                <label class="jr-lbl">Reversal date</label>
                                <input type="date" name="reversal_date" class="jr-in" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="jr-lbl">New reference</label>
                                <input type="text" name="reference" class="jr-in jr-in-mono" maxlength="60" value="REV-{{ $entry['no'] }}" readonly>
                            </div>
                        </div>
                        <label class="jr-lbl">Description <span class="jr-req">*</span></label>
                        <input type="text" name="memo" class="jr-in" maxlength="1000" required
                               placeholder="Reason for the reversal (kept in the audit trail)">
                        <div class="jr-radios">
                            <label><input type="radio" name="post_mode" value="immediate" checked> Post immediately</label>
                            <label><input type="radio" name="post_mode" value="draft"> Save as draft</label>
                        </div>
                    </form>
                </div>
                <div class="jr-mbox-f">
                    <span class="jr-note">The original stays on the register as Reversed.</span>
                    <div class="jr-mf-acts">
                        <button type="button" class="jr-btn jr-btn-g" @click="closeReverse()">Cancel</button>
                        <button type="button" class="jr-btn jr-btn-danger" @click="askReverse()">⟲ Confirm reversal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- ── shared delete modal ──────────────────────────────────────────────── --}}
<div class="jr-modal" id="jr-delete" :class="{ on: deleteId !== null }" @click.self="closeDelete()">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b>Delete journal <span x-text="deleteId !== null ? (get(deleteId)?.no || '') : ''"></span></b>
            <span class="jr-x" @click="closeDelete()" role="button" aria-label="Close">✕</span>
        </div>
        <div class="jr-mbox-b">
            <p>This permanently deletes <b x-text="deleteId !== null ? (get(deleteId)?.no || '') : ''"></b> and all of its lines. Only <b>unfinalized drafts</b> can be deleted, and only by the person who created them. This cannot be undone.</p>
            <form id="jr-delete-form" method="POST" :action="deleteId !== null && get(deleteId) ? get(deleteId).urls.destroy : '#'">
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
                <button type="button" class="jr-btn jr-btn-g" @click="closeDelete()">Cancel</button>
                <button type="submit" form="jr-delete-form" class="jr-btn jr-btn-danger">🗑 Delete permanently</button>
            </div>
        </div>
    </div>
</div>

{{-- ── shared reopen modal ──────────────────────────────────────────────── --}}
<div class="jr-modal" id="jr-reopen" :class="{ on: reopenId !== null }" @click.self="closeReopen()">
    <div class="jr-mbox">
        <div class="jr-mbox-h">
            <b>Reopen journal <span x-text="reopenId !== null ? (get(reopenId)?.no || '') : ''"></span></b>
            <span class="jr-x" @click="closeReopen()" role="button" aria-label="Close">✕</span>
        </div>
        <div class="jr-mbox-b">
            <p>Reopening returns <b x-text="reopenId !== null ? (get(reopenId)?.no || '') : ''"></b> to <b>Unfinalized</b> so its lines can be edited again. The reason is kept in the audit trail.</p>
            <form id="jr-reopen-form" method="POST" :action="reopenId !== null && get(reopenId) ? get(reopenId).urls.reopen : '#'">
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
                <button type="button" class="jr-btn jr-btn-g" @click="closeReopen()">Cancel</button>
                <button type="submit" form="jr-reopen-form" class="jr-btn jr-btn-p">⤺ Reopen as draft</button>
            </div>
        </div>
    </div>
</div>

{{-- ── shared confirm modal ─────────────────────────────────────────────── --}}
<div class="jr-modal jr-cf" id="jr-confirm" :class="{ on: cf.open }" @click.self="cf.open = false">
    <div class="jr-mbox jr-mbox--cf">
        <div class="jr-mbox-h">
            <b x-text="cf.title"></b>
            <span class="jr-x" @click="cf.open = false" role="button" aria-label="Close">✕</span>
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
