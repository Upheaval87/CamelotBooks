        <!-- ============ VIEW INVOICE MODAL ============ -->
        <div class="scrim" id="viewScrim">
            <div class="mbox" role="dialog" aria-modal="true">
                <div class="m-head">
                    <span class="m-ic"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg></span>
                    <div class="m-head-left"><span class="m-title">Invoice</span><span class="m-sub" id="vwRef">INV-2026-0000</span></div>
                    <span class="m-cust" id="vwCustH"></span>
                    <span class="pill" id="vwPill"></span>
                    <button class="m-close" data-close="viewScrim" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <div class="m-meta" id="vwMeta"></div>
                <div class="m-body">
                    <div class="mtiles" id="vwTiles"></div>
                    <div class="tabbar" id="vwTabs">
                        <button class="tab on" data-tab="ov">Overview</button>
                        <button class="tab" data-tab="li">Line items</button>
                        <button class="tab" data-tab="py">Payments</button>
                    </div>
                    <div class="tabpane on" id="pane-ov">
                        <div class="mrow"><span class="l">Customer</span><span class="v" id="vwCust">—</span></div>
                        <div class="mrow"><span class="l">Contact / phone</span><span class="v" id="vwContact">—</span></div>
                        <div class="mrow"><span class="l">Email</span><span class="v mono" id="vwEmail">—</span></div>
                        <div class="mrow"><span class="l">Bill-to address</span><span class="v" id="vwAddr">—</span></div>
                        <div class="mrow"><span class="l">Terms / due</span><span class="v" id="vwTerms">—</span></div>
                        <div class="mrow"><span class="l">Source document</span><span class="v mono" id="vwSource">—</span></div>
                        <div class="mrow"><span class="l">Customer reference</span><span class="v" id="vwRefNo">—</span></div>
                        <div class="mrow"><span class="l">Created by</span><span class="v" id="vwBy">—</span></div>
                        <div class="vsec" style="margin-top:14px">Notes</div>
                        <div style="font-size:12px;font-weight:500;color:var(--sub);line-height:1.65" id="vwNotes">—</div>
                    </div>
                    <div class="tabpane" id="pane-li">
                        <table class="ltable"><thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr></thead><tbody id="vwLines"></tbody></table>
                    </div>
                    <div class="tabpane" id="pane-py">
                        <table class="ltable"><thead><tr><th>Date</th><th>Method</th><th>Receipt №</th><th class="r">Amount</th></tr></thead><tbody id="vwPays"></tbody></table>
                        <div class="mrow" style="margin-top:10px"><span class="l">Total received</span><span class="v" id="vwPayTot">0.00</span></div>
                        <div class="mrow"><span class="l">Balance due</span><span class="v" id="vwBalDue">0.00</span></div>
                    </div>
                </div>
                <div class="m-foot" id="vwFoot"></div>
            </div>
        </div>

        <!-- ============ RECORD PAYMENT MODAL ============ -->
        <div class="scrim" id="payScrim">
            <div class="mbox narrow" role="dialog" aria-modal="true">
                <div class="m-head">
                    <span class="m-ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 10h8M8 14h8"/></svg></span>
                    <div class="m-head-left"><span class="m-title">Record payment</span><span class="m-sub" id="pyRef">INV-2026-0000</span></div>
                    <span class="m-cust" id="pyCustH"></span>
                    <button class="m-close" data-close="payScrim" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <div class="m-meta" id="pyMeta"></div>
                <div class="m-body">
                    <div class="g2">
                        <div class="fld"><label>Payment date <b>*</b></label><input class="in" type="date" id="pyDate"></div>
                        <div class="fld"><label>Amount ({{ $cs }}) <b>*</b></label><input class="in num" type="number" min="0" step="0.01" id="pyAmount" style="text-align:right"></div>
                    </div>
                    <div class="g2" style="margin-top:14px">
                        <div class="fld"><label>Payment method</label>
                            <select class="sel" id="pyMethod">
                                <option>Bank transfer</option>
                                <option>Mobile money</option>
                                <option>Cash</option>
                                <option>Card</option>
                                <option>Cheque</option>
                                <option>Split</option>
                            </select>
                        </div>
                        <div class="fld"><label>Receipt №</label><input class="in auto" id="pyReceipt" readonly value="{{ $nextReceipt }}"></div>
                    </div>
                    <div class="vsec" id="pyDetailsTitle" style="margin-top:14px">Method details — Bank transfer</div>
                    <div class="g2" id="pyDetails"></div>
                    <div id="pySplitWrap" style="display:none">
                        <div class="vsec" style="display:flex;align-items:center;gap:10px">Split tender legs <button class="btn-add" id="pySplitAdd" type="button" style="margin-left:auto"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add leg</button></div>
                        <table class="etable"><thead><tr><th>Method</th><th class="r" style="width:110px">Amount ({{ $cs }})</th><th style="width:34%">Reference</th><th style="width:34px"></th></tr></thead><tbody id="pySplitBody"></tbody></table>
                        <span class="splitsum bad" id="pySplitSum">Legs total {{ $cs }} 0.00 of {{ $cs }} 0.00</span>
                    </div>
                    <div class="fld" style="margin-top:12px"><label>Allocation note</label><input class="in" id="pyNote" placeholder="e.g. part settlement — balance next week"></div>
                    <div class="sumrow" style="margin-top:12px"><span>Balance after this payment</span><b id="pyAfter">—</b></div>
                </div>
                <div class="m-foot">
                    <span class="mnote">A confirmation step follows before anything is posted.</span>
                    <button class="btn btn-close btn-sm" data-close="payScrim"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>Close</button>
                    <button class="btn btn-green btn-sm" id="pySave"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>Review &amp; save</button>
                </div>
            </div>
        </div>

        <!-- ============ CONFIRM / SUCCESS ============ -->
        <div class="scrim cf" id="cfScrim">
            <div class="cbox" role="alertdialog" aria-modal="true">
                <div class="cb-head"><span class="cb-ic" id="cbIc"></span><b id="cbTitle">Confirm</b><button class="cb-x" data-close="cfScrim" aria-label="Cancel"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>
                <div class="cb-body" id="cbMsg"></div>
                <div class="cb-foot"><button class="btn btn-close btn-sm" data-close="cfScrim"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>Close</button><button class="btn btn-sm" id="cbOk">Confirm</button></div>
            </div>
        </div>
        <div class="scrim cf" id="okScrim">
            <div class="cbox" role="alertdialog" aria-modal="true">
                <div class="cb-head"><span class="cb-ic ok"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></span><b id="okTitle">Success</b><button class="cb-x" data-close="okScrim" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>
                <div class="cb-body" id="okBody"></div>
                <div class="cb-foot">
                    <button class="btn btn-close btn-sm" data-close="okScrim"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>Close</button>
                    <button class="btn btn-ghost btn-sm" id="okView"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>View invoice</button>
                    <button class="btn btn-cta btn-sm" id="okPrint" style="display:none"><svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Print / PDF</button>
                </div>
            </div>
        </div>

        <!-- ============ IN-APP FALLBACK PREVIEW (popup blocked only) ============ -->
        <div class="pview" id="printView" aria-hidden="true">
            <div class="ptoolbar">
                <button class="tb-back" id="pvBack"><svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Back</button>
                <span class="tb-title">Print preview — Tax Invoice <span id="pvRef">INV-2026-0000</span></span>
                <span class="tb-spacer"></span>
                <span class="tb-note">Popups blocked — in-app fallback · Ctrl+P prints</span>
                <button class="tb-btn" id="pvPdf"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>Save as PDF</button>
                <button class="tb-btn primary" id="pvPrint"><svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Print</button>
            </div>
            <div class="sheet-wrap"><div class="sheet" id="oSheet"></div></div>
        </div>

        <div class="toast" id="toast"></div>
    <script>
        window.INV_DATA = @json($ivPayload);
        window.INV_NEXT_RC = @json($nextReceipt);
        window.INV_CS = @json($cs);
        window.INV_OUTCOME = @json($invOutcome ?? null);
    </script>
