@php
    $cs = $systemCurrency;
    $accountStats = $stats;

    function _coaFlattenAccounts($accounts, $depth = 0) {
        $out = [];
        foreach ($accounts as $a) {
            $a['_depth'] = $depth;
            $out[] = $a;
            if (!empty($a['children'])) {
                $out = array_merge($out, _coaFlattenAccounts($a['children'], $depth + 1));
            }
        }
        return $out;
    }
@endphp

<x-app-layout>
    <div class="coa2-wrap" style="padding-top:24px;padding-bottom:24px">

        <div class="coa2-head">
            <div>
                <h1>{{ __('Chart of Accounts') }}</h1>
                <div class="coa2-sub">{{ $accountStats['total'] }} {{ __('accounts') }} &middot; {{ $accountStats['types'] }} {{ __('types') }} &middot; {{ __('system currency') }} ({{ $cs }}) &middot; {{ __('controlled accounts protected') }}</div>
            </div>
            <div class="coa2-head-btns">
                <button class="coa2-btn coa2-btn-ghost" id="coa2ExpandAll">{{ __('Expand All') }}</button>
                <button class="coa2-btn coa2-btn-ghost" id="coa2CollapseAll">{{ __('Collapse All') }}</button>
                <a href="{{ route('accounting.accounts.create') }}" class="coa2-btn coa2-btn-cta">{{ __('+ New Account') }}</a>
            </div>
        </div>

        <div class="coa2-eq">
            <div class="coa2-eq-cell">
                <span class="coa2-eq-l">{{ __('Total Assets') }}</span>
                <span class="coa2-eq-v">{{ format_number($equation['assets']) }}</span>
            </div>
            <div class="coa2-eq-sep"></div>
            <div class="coa2-eq-cell">
                <span class="coa2-eq-l">{{ __('Total Liabilities') }}</span>
                <span class="coa2-eq-v">{{ format_number($equation['liabilities']) }}</span>
            </div>
            <div class="coa2-eq-sep"></div>
            <div class="coa2-eq-cell">
                <span class="coa2-eq-l">{{ __('Total Equity') }}</span>
                <span class="coa2-eq-v">{{ format_number($equation['equity']) }}</span>
            </div>
            <span class="coa2-eq-bal" style="color:{{ $equation['balanced'] ? 'var(--green)' : 'var(--red-2)' }}">
                @if($equation['balanced'])
                    &#10003; {{ __('Balanced') }} &mdash; {{ __('Assets = Liabilities + Equity') }}
                @else
                    &#9888; {{ __('Out of balance by') }} {{ format_number($equation['difference']) }}
                @endif
            </span>
        </div>

        <div class="coa2-toolbar">
            <div class="coa2-seg" id="coa2Seg">
                <button data-v="tree" class="{{ $currentView === 'tree' ? 'on' : '' }}">{{ __('Hierarchy') }}</button>
                <button data-v="list" class="{{ $currentView === 'list' ? 'on' : '' }}">{{ __('List') }}</button>
            </div>
            <input class="coa2-search" id="coa2Q" placeholder="{{ __('Filter by code or name...') }}">
            <select class="coa2-sel" id="coa2Status">
                <option value="">{{ __('All statuses') }}</option>
                <option value="Active">{{ __('Active') }}</option>
                <option value="Inactive">{{ __('Inactive') }}</option>
                <option value="Controlled">{{ __('Controlled') }}</option>
            </select>
            <span class="coa2-tlabel">{{ __('Hide zero-balance') }}</span>
            <span class="coa2-sw" id="coa2ZeroSw"></span>
            <span class="coa2-count" id="coa2Count"></span>
        </div>

        <div class="coa2-view {{ $currentView === 'tree' ? 'on' : '' }}" id="coa2ViewTree">
            <div class="coa2-tree">
                <div class="coa2-tree-h">
                    <span></span><span>{{ __('Code') }}</span><span>{{ __('Account') }}</span>
                    <span>{{ __('Level') }}</span><span>{{ __('Status') }}</span>
                    <span class="coa2-num">{{ __('Opening') }} ({{ $cs }})</span>
                    <span class="coa2-num">{{ __('Current') }} ({{ $cs }})</span>
                    <span></span>
                </div>
                <div class="coa2-padwrap" id="coa2TreeBody">
                    @foreach($tree as $typeNode)
                        <div class="coa2-node">
                                <div class="coa2-row coa2-row-t" data-status="" data-open="0" data-cur="{{ $typeNode['total'] }}">
                                    <button class="coa2-car">&#9660;</button>
                                    <span class="coa2-code"></span>
                                    <span class="coa2-nm">{{ strtoupper($typeNode['label']) }}</span>
                                    <span><span class="coa2-chip coa2-lv-t">{{ __('Type') }}</span></span>
                                    <span></span>
                                    <span class="coa2-num coa2-roll-op"></span>
                                    <span class="coa2-num coa2-roll-cu"></span>
                                    <span></span>
                                </div>
                            <div class="coa2-kids">
                                @foreach($typeNode['sub_types'] as $subNode)
                                    <div class="coa2-node">
                                        <div class="coa2-row coa2-row-s" data-status="" data-open="0" data-cur="0">
                                            <button class="coa2-car">&#9660;</button>
                                            <span class="coa2-code"></span>
                                            <span class="coa2-nm">{{ $subNode['label'] }}</span>
                                            <span><span class="coa2-chip coa2-lv-s">{{ __('Sub-type') }}</span></span>
                                            <span></span>
                                            <span class="coa2-num coa2-roll-op"></span>
                                            <span class="coa2-num coa2-roll-cu"></span>
                                            <span></span>
                                        </div>
                                        <div class="coa2-kids">
                                            @foreach($subNode['accounts'] as $account)
                                                @include('accounting.accounts._coa-tree-node', ['node' => $account, 'depth' => 0])
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="coa2-view {{ $currentView === 'list' ? 'on' : '' }}" id="coa2ViewList">
            @foreach($tree as $typeNode)
                <div class="coa2-sec">
                    <div class="coa2-sec-h">
                        <span class="coa2-sec-t">{{ $typeNode['label'] }} {{ __('Accounts') }}</span>
                        <span class="coa2-sec-n">{{ count(array_filter($typeNode['sub_types'], fn($s) => !empty($s['accounts']))) }}</span>
                    </div>
                    <div class="coa2-tbl-wrap">
                        <table class="coa2-tbl">
                            <thead>
                                <tr>
                                    <th style="width:70px">{{ __('Code') }}</th>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Sub-type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="coa2-num">{{ __('Opening') }} ({{ $cs }})</th>
                                    <th class="coa2-num">{{ __('Current') }} ({{ $cs }})</th>
                                    <th style="width:110px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($typeNode['sub_types'] as $subNode)
                                    @foreach(_coaFlattenAccounts($subNode['accounts']) as $account)
                                        @include('accounting.accounts._coa-list-row', ['account' => $account, 'cs' => $cs])
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="coa2Menu">
        <div class="coa2-mhead" id="coa2MenuTitle"></div>
        <button data-a="ledger">{{ __('View ledger') }}</button>
        <button data-a="edit">{{ __('Edit account') }}</button>
        <button data-a="deact" id="coa2MenuDeact" class="danger"></button>
    </div>

    <div class="coa2-modal" id="coa2Modal">
        <div class="coa2-mbox">
            <div class="coa2-mbox-h"><h3 id="coa2MTitle">{{ __('Deactivate account') }}</h3></div>
            <div class="coa2-mbox-b">
                <div class="coa2-warn">{{ __('Future postings blocked; history retained. Permission-gated + audited.') }}</div>
                <div class="coa2-f">
                    <label>{{ __('Reason (mandatory)') }}</label>
                    <textarea id="coa2MReason" placeholder="{{ __('e.g. Replaced by 1000 - no activity since 2024') }}"></textarea>
                </div>
            </div>
            <div class="coa2-mbox-f">
                <button class="coa2-btn coa2-btn-ghost" id="coa2MCancel">{{ __('Cancel') }}</button>
                <button class="coa2-btn coa2-btn-danger" id="coa2MOk">{{ __('Deactivate') }}</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function(){
        var treeData = @json($tree);
        var balances = @json($balances);
        var deactivateUrl = '{{ route("accounting.accounts.deactivate", ":id") }}';
        var reactivateUrl = '{{ route("accounting.accounts.reactivate", ":id") }}';
        var editUrl = '{{ route("accounting.accounts.edit", ":id") }}';
        var ledgerUrl = '{{ route("accounting.accounts.show", ":id") }}';
        var csrfToken = '{{ csrf_token() }}';

        function fmt(n){var a=Math.abs(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});return n<0?'('+a+')':a;}

        function roll(node){
            var o=0,c=0;
            node.querySelectorAll(':scope > .coa2-kids .coa2-leaf').forEach(function(l){o+=parseFloat(l.dataset.open)||0;c+=parseFloat(l.dataset.cur)||0;});
            node.querySelectorAll(':scope > .coa2-kids .coa2-node').forEach(function(n){var r=roll(n);o+=r.o;c+=r.c;});
            var h=node.querySelector(':scope > .coa2-row');
            if(h){
                var opEl=h.querySelector('.coa2-roll-op');
                var cuEl=h.querySelector('.coa2-roll-cu');
                if(opEl)opEl.textContent=fmt(o);
                if(cuEl){cuEl.textContent=fmt(c);cuEl.classList.toggle('neg',c<0);}
            }
            return{o:o,c:c};
        }

        var tops=document.querySelectorAll('#coa2TreeBody > .coa2-node');
        tops.forEach(function(t){roll(t);});

        document.getElementById('coa2Seg').addEventListener('click',function(e){
            var b=e.target.closest('button');if(!b)return;
            document.querySelectorAll('#coa2Seg button').forEach(function(x){x.classList.remove('on');});
            b.classList.add('on');
            document.getElementById('coa2ViewTree').classList.toggle('on',b.dataset.v==='tree');
            document.getElementById('coa2ViewList').classList.toggle('on',b.dataset.v==='list');
            applyFilter();
            fetch('{{ route("accounting.accounts.preference") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},body:JSON.stringify({view:b.dataset.v})});
        });

        document.querySelectorAll('.coa2-car').forEach(function(c){
            c.addEventListener('click',function(e){
                e.stopPropagation();
                c.closest('.coa2-node').classList.toggle('collapsed');
            });
        });
        document.getElementById('coa2ExpandAll').addEventListener('click',function(){document.querySelectorAll('.coa2-node').forEach(function(n){n.classList.remove('collapsed');});});
        document.getElementById('coa2CollapseAll').addEventListener('click',function(){document.querySelectorAll('.coa2-node').forEach(function(n){n.classList.add('collapsed');});});

        var q=document.getElementById('coa2Q'),st=document.getElementById('coa2Status'),zero=document.getElementById('coa2ZeroSw'),countEl=document.getElementById('coa2Count');

        function matches(el){
            var term=q.value.trim().toLowerCase();
            var hideQ=term&&el.textContent.toLowerCase().indexOf(term)===-1;
            var hideS=st.value&&el.dataset.status!==st.value;
            var hideZ=zero.classList.contains('on')&&parseFloat(el.dataset.cur)===0&&parseFloat(el.dataset.open)===0;
            return !(hideQ||hideS||hideZ);
        }

        function applyFilter(){
            var shown=0;
            document.querySelectorAll('#coa2ViewTree .coa2-leaf').forEach(function(l){var ok=matches(l);l.style.display=ok?'':'none';if(ok)shown++;});
            document.querySelectorAll('#coa2ViewTree .coa2-node').forEach(function(n){
                var has=[].slice.call(n.querySelectorAll('.coa2-leaf')).some(function(l){return l.style.display!=='none';});
                var isLeaf=n.querySelector(':scope > .coa2-leaf');
                n.style.display=(has||isLeaf)?'':'none';
                if(q.value||st.value||zero.classList.contains('on'))n.classList.remove('collapsed');
            });
            document.querySelectorAll('#coa2ViewList .coa2-lrow').forEach(function(r){var ok=matches(r);r.style.display=ok?'':'none';if(ok)shown++;});
            document.querySelectorAll('#coa2ViewList .coa2-sec').forEach(function(s){
                var hasVisible=[].slice.call(s.querySelectorAll('.coa2-lrow')).some(function(r){return r.style.display!=='none';});
                s.style.display=hasVisible?'':'none';
            });
            countEl.textContent=shown+' {{ __("accounts shown") }}';
        }
        q.addEventListener('input',applyFilter);st.addEventListener('change',applyFilter);
        zero.addEventListener('click',function(){zero.classList.toggle('on');applyFilter();});
        applyFilter();

        var menu=document.getElementById('coa2Menu'),menuDeact=document.getElementById('coa2MenuDeact');
        var modal=document.getElementById('coa2Modal'),activeRow=null,activeAccountId=null;

        document.addEventListener('click',function(e){
            var more=e.target.closest('.coa2-more');
            if(more){
                activeRow=more.closest('tr')||more.closest('.coa2-row');
                activeAccountId=activeRow.dataset.id;
                document.getElementById('coa2MenuTitle').textContent=(activeRow.dataset.code||'')+' \u00B7 '+(activeRow.dataset.name||'');
                var isInactive=activeRow.dataset.status==='Inactive';
                var isControlled=activeRow.dataset.status==='Controlled';
                menuDeact.disabled=isControlled;
                menuDeact.textContent=isControlled?'{{ __("Controlled - cannot deactivate") }}':(isInactive?'{{ __("Reactivate account") }}':'{{ __("Deactivate account") }}');
                menuDeact.classList.toggle('danger',!isControlled&&!isInactive);
                var r=more.getBoundingClientRect();
                menu.style.top=Math.min(r.bottom+6,innerHeight-200)+'px';
                menu.style.left=Math.max(12,r.left-160)+'px';
                menu.classList.add('on');e.stopPropagation();return;
            }
            if(!e.target.closest('#coa2Menu'))menu.classList.remove('on');
        });

        menu.addEventListener('click',function(e){
            var b=e.target.closest('button');if(!b||b.disabled)return;
            menu.classList.remove('on');
            var action=b.dataset.a;
            if(action==='ledger'){window.location=ledgerUrl.replace(':id',activeAccountId);return;}
            if(action==='edit'){window.location=editUrl.replace(':id',activeAccountId);return;}
            if(action!=='deact')return;
            if(activeRow.dataset.status==='Inactive'){
                fetch(reactivateUrl.replace(':id',activeAccountId),{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}})
                .then(function(r){return r.json();}).then(function(d){
                    if(d.ok){activeRow.dataset.status='Active';
                    var oldChip=activeRow.querySelector('.coa2-st-i');if(oldChip)oldChip.outerHTML='<span class="coa2-chip coa2-st-a">{{ __("Active") }}</span>';
                    var oldCell=activeRow.querySelector('.coa2-chip.st-i');if(oldCell)oldCell.outerHTML='<span class="coa2-chip coa2-st-a">{{ __("Active") }}</span>';
                    applyFilter();}
                });return;
            }
            document.getElementById('coa2MTitle').textContent='{{ __("Deactivate") }} '+(activeRow.dataset.code||'')+' \u00B7 '+(activeRow.dataset.name||'');
            document.getElementById('coa2MReason').value='';modal.classList.add('on');
        });

        document.getElementById('coa2MCancel').addEventListener('click',function(){modal.classList.remove('on');});
        modal.addEventListener('click',function(e){if(e.target===modal)modal.classList.remove('on');});

        document.getElementById('coa2MOk').addEventListener('click',function(){
            var reason=document.getElementById('coa2MReason').value.trim();
            if(!reason){document.getElementById('coa2MReason').focus();return;}
            fetch(deactivateUrl.replace(':id',activeAccountId),{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},body:JSON.stringify({reason:reason})})
            .then(function(r){return r.json();}).then(function(d){
                if(d.ok){
                    activeRow.dataset.status='Inactive';
                    var oldChip=activeRow.querySelector('.coa2-st-a');if(oldChip)oldChip.outerHTML='<span class="coa2-chip coa2-st-i">{{ __("Inactive") }}</span>';
                    var oldCell=activeRow.querySelector('.coa2-chip.st-a');if(oldCell)oldCell.outerHTML='<span class="coa2-chip coa2-st-i">{{ __("Inactive") }}</span>';
                    modal.classList.remove('on');applyFilter();
                }
            });
        });
    })();
    </script>
    @endpush
</x-app-layout>