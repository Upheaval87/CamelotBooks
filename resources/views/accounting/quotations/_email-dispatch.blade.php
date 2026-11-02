{{--
    §2.2–§5 EMAIL DISPATCH — the four-stage progress modal, the background dock
    and their polling client.

    Appendix A port. Every class is prefixed `qd-` and every rule is scoped, so
    this file can never be half-styled by a stale compiled bundle and can never
    collide with the register's global `.btn` / `.scrim` / `.toast` families.

    The modal + dock markup is STATIC (all copy lives in the DOM, escaped by
    Blade) and the client only fills in values and toggles state — so a slow or
    failed job can never leave an empty shell behind.

    `window.QDispatch` is the only surface the register touches (the
    `data-qact="email"` handler in index.blade.php).
--}}
@php
    /** @var array<int, array{key: string, label: string}> $qdStages */
    $qdStages = collect(\App\Models\QuotationEmailJob::STAGE_LABELS)
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
        ->values()
        ->all();
@endphp

<div class="qd" id="qd-root">

    {{-- ------------------------------------------------------------------ --}}
    {{-- §2.2 — the dispatch modal                                             --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="qd-scrim" id="qd-scrim" hidden>
        <div class="qd-cbox" id="qd-modal" role="alertdialog" aria-modal="true" tabindex="-1"
             aria-labelledby="qd-title" aria-describedby="qd-sub">
            <div class="qd-cbhead">
                <span class="qd-cbic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5v-9z" stroke="currentColor" stroke-width="1.9"/><path d="M3.6 7.2L12 13l8.4-5.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                </span>
                <b id="qd-headref">{{ __('Email dispatch') }}</b>
                <button type="button" class="qd-cbx" id="qd-min" data-qd="minimize"
                        title="{{ __('Minimize to background') }}" aria-label="{{ __('Minimize to background') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9h12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                </button>
                <button type="button" class="qd-cbx" id="qd-x" data-qd="close"
                        title="{{ __('Cancel & close') }}" aria-label="{{ __('Cancel & close') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div class="qd-cbbody">
                <div class="qd-mpwrap">
                    <div class="qd-mpenv" aria-hidden="true">
                        <span class="qd-ring"></span>
                        <span class="qd-ring qd-ring--two"></span>
                        <span class="qd-mpic" id="qd-mpic">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5v-9z" stroke="currentColor" stroke-width="1.8"/><path d="M3.6 7.2L12 13l8.4-5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                    </div>

                    <div class="qd-mptitle" id="qd-title">{{ __('Sending quotation') }}</div>
                    <div class="qd-mpsub" id="qd-sub"></div>

                    <div class="qd-mpbar" role="progressbar" id="qd-pbar"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="{{ __('Email dispatch progress') }}">
                        <i id="qd-fill"></i>
                        <span class="qd-mpshine" aria-hidden="true"></span>
                    </div>

                    <div class="qd-mppctrow">
                        <span class="qd-mplog" id="qd-log">{{ __('Preparing PDF document') }}</span>
                        <span class="qd-mppct" id="qd-pct">0%</span>
                    </div>

                    <ol class="qd-mpsteps">
                        @foreach ($qdStages as $qdIndex => $qdStage)
                            <li class="qd-mpstep" data-qd-step="{{ $qdIndex }}">
                                <span class="qd-st" aria-hidden="true"></span>{{ $qdStage['label'] }}
                            </li>
                        @endforeach
                    </ol>

                    <p class="qd-mhint" id="qd-hint">{{ __('Running in background if minimized — you can keep working.') }}</p>
                </div>
            </div>

            <div class="qd-cbfoot" id="qd-foot">
                <span class="qd-mnote" id="qd-note" hidden>
                    <b>{{ __('Email could not be delivered') }}</b><span id="qd-reason"></span>
                </span>
                <span class="qd-fspacer"></span>
                <button type="button" class="qd-btn qd-btn--ghost" id="qd-cancel" data-qd="cancel">{{ __('Cancel & close') }}</button>
                <button type="button" class="qd-btn qd-btn--ghost" id="qd-minimize" data-qd="minimize">{{ __('Minimize to background') }}</button>
                <button type="button" class="qd-btn qd-btn--close" id="qd-close" data-qd="close">{{ __('Close') }}</button>
                <button type="button" class="qd-btn qd-btn--cta" id="qd-retry" data-qd="retry">{{ __('Try again') }}</button>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- §3.3 — the minimized background dock                                 --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="qd-dock" id="qd-dock" role="status" aria-live="polite" hidden>
        <div class="qd-dh">
            <span class="qd-dic" id="qd-dic" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 16.5v-9z" stroke="currentColor" stroke-width="1.9"/><path d="M3.6 7.2L12 13l8.4-5.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
            </span>
            <span class="qd-dspn" id="qd-dspn" aria-hidden="true"></span>
            <span class="qd-dtxt">
                <span class="qd-dt" id="qd-dt"></span>
                <span class="qd-ds" id="qd-ds"></span>
            </span>
            <button type="button" class="qd-dx" data-qd="dismiss"
                    title="{{ __('Dismiss') }}" aria-label="{{ __('Dismiss') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
            </button>
        </div>

        <div class="qd-db" id="qd-db">
            <div class="qd-dbar"><i id="qd-dfill"></i></div>
            <div class="qd-drrow">
                <span class="qd-drst" id="qd-drs">{{ __('Sending') }}</span>
                <span class="qd-drpct" id="qd-drpct">0%</span>
            </div>
        </div>

        {{-- Actions live outside .qd-db / .qd-dres: both of those are swapped
             out per state, which would strand these buttons in a hidden
             ancestor and make a settled dock impossible to act on. --}}
        <div class="qd-dacts" id="qd-dacts">
            <button type="button" class="qd-btn qd-btn--cta qd-btn--sm" id="qd-dopen" data-qd="restore">{{ __('Open') }}</button>
            <button type="button" class="qd-btn qd-btn--ghost qd-btn--sm" id="qd-dview" data-qd="view">{{ __('View quotation') }}</button>
            <button type="button" class="qd-btn qd-btn--ghost qd-btn--sm" id="qd-dretry" data-qd="retry">{{ __('Try again') }}</button>
            <button type="button" class="qd-btn qd-btn--ghost qd-btn--sm" id="qd-ddismiss" data-qd="dismiss">{{ __('Dismiss') }}</button>
        </div>

        <div class="qd-dres" id="qd-dres" hidden>
            <span class="qd-dric2" id="qd-dric2" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" id="qd-dric2svg"><path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="qd-drt2">
                <b id="qd-drt"></b>
                <span id="qd-drs2"></span>
            </span>
        </div>
    </div>
</div>

<style>
    /* The `.qd` wrapper must NOT create a stacking context (no position /
       z-index), or the fixed scrim + dock would be trapped below the register's
       z-120 workspace modal. Both are stacked above it instead. */
    .qd { --qd-scrim-z: 145; --qd-dock-z: 235; }

    /* ---- §2.2 scrim: a SOLID tint. A blurred backdrop both looks wrong over a
         dense register and repaints the whole page on every frame. ---- */
    .qd-scrim {
        position: fixed; inset: 0; z-index: var(--qd-scrim-z);
        display: grid; place-items: center; padding: 20px;
        background: rgba(11, 42, 45, .45);
        overflow-y: auto;
    }
    .qd-scrim[hidden] { display: none; }

    .qd-cbox {
        width: min(450px, 100%); background: #fff;
        border-radius: 18px; overflow: hidden;
        box-shadow: 0 24px 60px -18px rgba(8, 40, 44, .55);
        animation: qd-cboxin .2s ease-out both;
    }
    @keyframes qd-cboxin { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }

    .qd-cbhead {
        display: flex; align-items: center; gap: 11px;
        padding: 14px 18px; border-bottom: 1px solid var(--line, #E2ECEC);
        background: linear-gradient(180deg, #fdfeff, #f6fbf9);
    }
    .qd-cbhead b { flex: 1 1 auto; min-width: 0; font-size: 14px; font-weight: 800; color: var(--ink, #0B2A2D); }
    .qd-cbic {
        display: grid; place-items: center; flex: none;
        width: 30px; height: 30px; border-radius: 9px;
        background: rgba(18, 143, 142, .1); color: var(--sec, #128F8E);
    }
    .qd-cbic svg { width: 15px; height: 15px; }
    .qd-cbx {
        flex: none; display: grid; place-items: center; cursor: pointer;
        width: 26px; height: 26px; border-radius: 7px;
        border: 1px solid transparent; background: none; color: var(--faint, #8AA5A7);
        transition: background .14s ease, color .14s ease;
    }
    .qd-cbx svg { width: 13px; height: 13px; }
    .qd-cbx:hover { background: #F0F6F6; color: var(--ink, #0B2A2D); }
    .qd-cbx[hidden] { display: none; }

    .qd-cbbody { padding: 16px 18px; font-size: 12.5px; line-height: 1.65; color: var(--sub, #1B3B3E); }

    /* ---- the medallion: envelope in a gradient disc with two pulse rings ---- */
    .qd-mpwrap { text-align: center; padding: 6px 2px 2px; }
    .qd-mpenv {
        position: relative; display: grid; place-items: center;
        width: 66px; height: 66px; margin: 0 auto 14px; border-radius: 50%;
        background: radial-gradient(circle at 32% 26%, #35d3c7, #128F8E 72%);
        color: #EAFFFF;
        box-shadow: 0 10px 22px -10px rgba(18, 143, 142, .8);
        transition: background .3s ease, box-shadow .3s ease;
    }
    .qd-mpenv .qd-mpic { display: grid; place-items: center; }
    .qd-mpenv svg { width: 26px; height: 26px; }
    .qd-ring {
        position: absolute; inset: 0; border-radius: 50%;
        border: 2px solid rgba(18, 143, 142, .35);
        animation: qd-ping 1.7s ease-out infinite;
    }
    .qd-ring--two { animation-delay: .55s; }
    @keyframes qd-ping { 0% { transform: scale(1); opacity: .7; } 100% { transform: scale(1.5); opacity: 0; } }

    .qd-mptitle { font-size: 15px; font-weight: 900; color: var(--ink, #0B2A2D); }
    .qd-mpsub { margin-top: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 700; color: var(--muted, #5F7476); overflow-wrap: anywhere; }

    .qd-mpbar {
        position: relative; height: 8px; margin: 18px 0 8px;
        border-radius: 999px; background: var(--line, #E2ECEC); overflow: hidden;
    }
    .qd-mpbar > i {
        position: absolute; left: 0; top: 0; bottom: 0; width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, #35d3c7, #128F8E);
        transition: width .12s linear;
    }
    .qd-mpshine {
        position: absolute; top: 0; bottom: 0; left: 0; width: 46px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .6), transparent);
        animation: qd-shine 1.15s linear infinite;
    }
    @keyframes qd-shine { from { transform: translateX(-46px); } to { transform: translateX(101%); } }

    .qd-mppctrow { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .qd-mplog { font-size: 10.5px; font-weight: 700; color: var(--muted, #5F7476); text-align: left; }
    .qd-mppct { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 800; color: var(--sec, #128F8E); }

    .qd-mpsteps { margin: 14px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; text-align: left; }
    .qd-mpstep { display: flex; align-items: center; gap: 10px; font-size: 11.5px; font-weight: 700; color: var(--faint, #8AA5A7); transition: color .2s ease; }
    .qd-st {
        position: relative; flex: 0 0 auto; width: 16px; min-width: 16px; height: 16px;
        border-radius: 50%; border: 2px solid var(--line, #E2ECEC); background: #fff;
        transition: border-color .2s ease, background .2s ease;
    }
    .qd-mpstep.is-active { color: var(--ink, #0B2A2D); }
    .qd-mpstep.is-active .qd-st { border-color: var(--sec, #128F8E); border-top-color: transparent; animation: qd-spin .8s linear infinite; }
    @keyframes qd-spin { to { transform: rotate(360deg); } }
    .qd-mpstep.is-done { color: var(--green, #15803D); }
    .qd-mpstep.is-done .qd-st { border-color: var(--green, #15803D); background: var(--green, #15803D); animation: none; }
    .qd-mpstep.is-done .qd-st::after {
        content: ''; position: absolute; left: 4px; top: 1px; width: 4px; height: 8px;
        border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(42deg);
    }
    .qd-mpstep.is-fail { color: var(--red-2, #B91C1C); }
    .qd-mpstep.is-fail .qd-st { border-color: var(--red-2, #B91C1C); background: var(--red-2, #B91C1C); animation: none; }
    .qd-mpstep.is-fail .qd-st::before, .qd-mpstep.is-fail .qd-st::after {
        content: ''; position: absolute; left: 1px; top: 6px; width: 10px; height: 2px;
        background: #fff; border-radius: 2px;
    }
    .qd-mpstep.is-fail .qd-st::before { transform: rotate(45deg); }
    .qd-mpstep.is-fail .qd-st::after { transform: rotate(-45deg); }

    /* A cancelled send is neutral, not an error: slate dot with a pause mark. */
    .qd-mpstep.is-cancel { color: var(--muted, #5F7476); }
    .qd-mpstep.is-cancel .qd-st { border-color: #94A3B8; background: #94A3B8; animation: none; }
    .qd-mpstep.is-cancel .qd-st::before {
        content: ''; position: absolute; left: 4px; top: 3px; width: 3px; height: 8px;
        background: #fff; border-radius: 2px; box-shadow: 5px 0 0 #fff;
    }

    .qd-mhint { margin: 12px 0 0; font-size: 11px; font-weight: 600; color: var(--muted, #5F7476); }

    /* ---- footer ---- */
    .qd-cbfoot {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        padding: 13px 18px; border-top: 1px solid var(--line, #E2ECEC); background: #f8fafa;
    }
    .qd-fspacer { flex: 1 1 auto; }
    .qd-mnote {
        display: block; flex: 1 1 200px; margin-right: auto;
        padding: 8px 12px; border-radius: 9px;
        background: #f6fbf9; border: 1px solid var(--line, #E2ECEC);
        font-size: 11.5px; font-weight: 600; color: var(--muted, #5F7476); text-align: left;
    }
    .qd-mnote b { display: block; margin-bottom: 2px; font-weight: 800; color: var(--red-2, #B91C1C); }
    .qd-mnote[hidden] { display: none; }

    .qd-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        height: 38px; padding: 0 15px; border-radius: 11px; cursor: pointer;
        font-size: 12.5px; font-weight: 700; white-space: nowrap;
        border: 1px solid transparent; transition: background .16s ease, border-color .16s ease, transform .16s ease, box-shadow .16s ease;
    }
    .qd-btn:hover { transform: translateY(-1px); }
    .qd-btn svg { width: 14px; height: 14px; }
    .qd-btn--ghost { background: #fff; border-color: var(--border, #DCEAEA); color: var(--sub, #1B3B3E); box-shadow: 0 1px 2px rgba(11, 42, 45, .05); }
    .qd-btn--ghost:hover { background: #F4F8F8; border-color: var(--mint, #7FD1C0); }
    .qd-btn--cta {
        color: #EAFFFF; background: linear-gradient(180deg, #149897, #128F8E);
        box-shadow: 0 5px 12px -5px rgba(18, 143, 142, .6), inset 0 1px 0 rgba(255, 255, 255, .22);
    }
    .qd-btn--cta:hover { filter: brightness(1.04); }
    .qd-btn--close { background: #fff; border: 1px solid var(--border, #DCEAEA); color: var(--ink, #0B2A2D); font-weight: 800; }
    .qd-btn--close:hover { background: #F4F8F8; border-color: var(--mint, #7FD1C0); }
    .qd-btn--sm { height: 34px; padding: 0 13px; font-size: 12px; border-radius: 10px; }
    .qd-btn[hidden] { display: none; }

    /* ---- §3.3 dock ---- */
    .qd-dock {
        position: fixed; right: 22px; bottom: 22px; z-index: var(--qd-dock-z);
        width: 302px; max-width: calc(100vw - 44px);
        background: #fff; border: 1px solid var(--line, #E2ECEC); border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 40px -14px rgba(8, 40, 44, .5);
        animation: qd-dockin .28s ease-out both;
    }
    @keyframes qd-dockin { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .qd-dock[hidden] { display: none; }

    .qd-dh { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: linear-gradient(180deg, #11454b, #0c3539); color: #fff; transition: background .3s ease; }
    .qd-dock.is-ok .qd-dh { background: linear-gradient(180deg, #1a9a4f, var(--green, #15803D)); }
    .qd-dock.is-fail .qd-dh { background: linear-gradient(180deg, #e05252, var(--red-2, #B91C1C)); }
    .qd-dock.is-cancel .qd-dh { background: linear-gradient(180deg, #7E8C9A, #55636F); }
    .qd-dic {
        display: grid; place-items: center; flex: none;
        width: 28px; min-width: 28px; height: 28px; border-radius: 9px;
        background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .16);
    }
    .qd-dic svg { width: 14px; height: 14px; }
    .qd-dspn {
        flex: none; width: 14px; height: 14px; border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, .35); border-top-color: #fff;
        animation: qd-spin .8s linear infinite;
    }
    .qd-dspn[hidden] { display: none; }
    .qd-dtxt { flex: 1 1 auto; min-width: 0; }
    .qd-dt { display: block; font-size: 11px; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .qd-ds { display: block; margin-top: 2px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 9.5px; font-weight: 700; color: rgba(255, 255, 255, .62); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .qd-dx {
        flex: none; display: grid; place-items: center; cursor: pointer;
        width: 24px; min-width: 24px; height: 24px; border-radius: 7px;
        border: 1px solid rgba(255, 255, 255, .16); background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .85);
        transition: background .14s ease;
    }
    .qd-dx svg { width: 11px; height: 11px; }
    .qd-dx:hover { background: rgba(255, 255, 255, .2); }

    .qd-db { padding: 10px 12px 12px; }
    .qd-db[hidden] { display: none; }
    .qd-dbar { position: relative; height: 6px; border-radius: 999px; background: var(--line, #E2ECEC); overflow: hidden; }
    .qd-dbar > i {
        position: absolute; left: 0; top: 0; bottom: 0; width: 0%; border-radius: 999px;
        background: linear-gradient(90deg, #35d3c7, #128F8E);
        transition: width .12s linear;
    }
    .qd-drrow { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 7px; }
    .qd-drst { font-size: 10px; font-weight: 700; color: var(--muted, #5F7476); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .qd-drpct { flex: none; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 10px; font-weight: 800; color: var(--sec, #128F8E); }
    .qd-dacts { display: flex; align-items: center; justify-content: flex-end; gap: 6px; padding: 0 12px 12px; }
    .qd-dacts[hidden] { display: none; }
    .qd-dacts .qd-btn[hidden] { display: none; }

    .qd-dres { display: flex; align-items: center; gap: 10px; padding: 11px 12px; }
    .qd-dres[hidden] { display: none; }
    .qd-dric2 { display: grid; place-items: center; flex: none; width: 30px; min-width: 30px; height: 30px; border-radius: 50%; color: #fff; }
    .qd-dric2.is-ok { background: linear-gradient(180deg, #1a9a4f, var(--green, #15803D)); }
    .qd-dric2.is-bad { background: linear-gradient(180deg, #e05252, var(--red-2, #B91C1C)); }
    .qd-dric2.is-cancel { background: linear-gradient(180deg, #7E8C9A, #55636F); }
    .qd-dric2 svg { width: 15px; height: 15px; }
    .qd-drt2 { flex: 1 1 auto; min-width: 0; }
    .qd-drt2 b { display: block; font-size: 11.5px; font-weight: 800; color: var(--ink, #0B2A2D); }
    .qd-drt2 span { display: block; margin-top: 2px; font-size: 10px; font-weight: 600; color: var(--muted, #5F7476); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* ---- freeze states: success + failure stop every animation ---- */
    .qd-scrim.is-done .qd-mpenv { background: linear-gradient(180deg, #1a9a4f, var(--green, #15803D)); box-shadow: 0 10px 22px -10px rgba(21, 128, 61, .7); }
    .qd-scrim.is-done .qd-ring, .qd-scrim.is-fail .qd-ring { animation: none; opacity: 0; }
    .qd-scrim.is-done .qd-mpbar > i { background: linear-gradient(90deg, #1a9a4f, var(--green, #15803D)); }
    .qd-scrim.is-done .qd-mpshine, .qd-scrim.is-fail .qd-mpshine { display: none; }
    .qd-scrim.is-done .qd-mppct { color: var(--green, #15803D); }

    .qd-scrim.is-fail .qd-mpenv { background: linear-gradient(180deg, #e05252, var(--red-2, #B91C1C)); box-shadow: 0 10px 22px -10px rgba(185, 28, 28, .7); }
    .qd-scrim.is-fail .qd-mpbar > i { background: linear-gradient(90deg, #e05252, var(--red-2, #B91C1C)); }
    .qd-scrim.is-fail .qd-mppct { color: var(--red-2, #B91C1C); }
    .qd-scrim.is-fail .qd-mplog { color: var(--red-2, #B91C1C); }
    /* Cancelled: same frozen envelope, neutral slate accents (never red). */
    .qd-scrim.is-cancel .qd-ring { animation: none; opacity: 0; }
    .qd-scrim.is-cancel .qd-mpenv { background: linear-gradient(180deg, #7E8C9A, #55636F); box-shadow: 0 10px 22px -10px rgba(71, 85, 105, .7); }
    .qd-scrim.is-cancel .qd-mpbar > i { background: linear-gradient(90deg, #94A3B8, #64748B); }
    .qd-scrim.is-cancel .qd-mppct, .qd-scrim.is-cancel .qd-mplog { color: var(--muted, #5F7476); }
    .qd-scrim.is-cancel .qd-mpshine { display: none; }

    /* Focus-visible rings are required on every interactive affordance. */
    .qd :focus-visible { outline: 2px solid var(--sec, #128F8E); outline-offset: 2px; border-radius: 8px; }

    @media (max-width: 760px) {
        .qd-scrim { padding: 0; place-items: stretch; }
        .qd-cbox { width: 100%; min-height: 100%; border-radius: 0; align-self: flex-start; }
        .qd-dock { left: 12px; right: 12px; bottom: 12px; width: auto; max-width: none; }
    }
    @media (max-width: 560px) {
        .qd-dacts { flex-wrap: wrap; }
        .qd-dacts .qd-btn { flex: 1 1 auto; }
        .qd-cbfoot { align-items: stretch; }
        .qd-cbfoot .qd-mnote { flex: 1 1 100%; }
    }

    @media print {
        .qd, .qd-scrim, .qd-dock { display: none !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        .qd *, .qd *::before, .qd *::after { animation: none !important; transition: none !important; }
    }
</style>

<script>
    (function () {
        'use strict';

        var STAGES = @json(array_values($qdStages));

        var COPY = {
            /* §2.2 modal titles. */
            head: @json(__('Email dispatch')),
            sendingTitle: @json(__('Sending quotation')),
            okTitle: @json(__('Quotation delivered')),
            failTitle: @json(__('Delivery failed')),
            deliveredLog: @json(__('Delivered')),
            sendingLog: @json(__('Sending')),

            /* §4.4 cancel. */
            cancelTitle: @json(__('Email send cancelled')),
            cancelToast: @json(__('✋ Email send cancelled — quotation unchanged')),
            cancelSub: @json(__('Cancelled — the quotation is unchanged.')),

            /* §4.3 failure detail. */
            failSub: @json(__('Delivery failed — the quotation is unchanged.')),
            genericFail: @json(__('Delivery failed — the quotation is unchanged.')),

            /* §5 minimize + background results. */
            minTitle: @json(__('Email dispatch minimized')),
            minToast: @json(__('⇩ Dispatch minimized — running in background')),
            bgOkTitle: @json(__('Email delivered in background')),
            bgOkToast: @json(__('📨 {ref} delivered to {email}')),
            bgFailTitle: @json(__('Email delivery failed in background')),
            bgFailToast: @json(__('⚠ {ref} delivery failed — {reason}')),
            /* §4.5 — a completed failure that the user closes reports once. */
            closeFailToast: @json(__('⚠ Delivery failed — {reason}')),

            /* confirm + validation copy. */
            askTitle: @json(__('Email {ref} to {email}?')),
            askMessage: @json(__('Sends the quotation PDF to the customer and records the dispatch.')),
            askConfirm: @json(__('Send now')),
            askCancel: @json(__('Cancel')),
            noRecipient: @json(__('This quotation has no valid email address on file.')),

            view: @json(__('View quotation')),
            dismiss: @json(__('Dismiss')),
            okDock: @json(__('{ref} delivered')),
            failDock: @json(__('Delivery failed')),
            cancelDock: @json(__('Email send cancelled'))
        };

        var ICON_OK = '<path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>';
        var ICON_CANCEL = '<path d="M9.5 7v10M14.5 7v10" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>';
        var ICON_BAD = '<path d="M7 7l10 10M17 7L7 17" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>';

        var el = {};
        var state = {
            job: null, quotation: null, timer: null, busy: false,
            lastFocus: null, reopenId: null, minimized: false,
            shown: 0, target: 0, raf: null
        };

        function $(id) { return document.getElementById(id); }

        function csrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') : '';
        }

        /* §5 — every dismissal reports through the shared CB toast surface. */
        function toast(type, title, message) {
            if (window.CB && typeof window.CB.toast === 'function') { window.CB.toast(type, title, message || ''); }
        }

        function fill(str, vars) {
            return String(str).replace(/\{(\w+)\}/g, function (m, k) { return vars[k] == null ? '' : String(vars[k]); });
        }

        function refOf(job) {
            return (job && job.quotation && job.quotation.number) ? job.quotation.number : '';
        }

        function reopenWorkspace(id) {
            if (!id) return;
            try { sessionStorage.removeItem('quot.dispatch.reopen'); } catch (e) {}
            if (typeof window.quotOpenView === 'function') window.quotOpenView(id);
        }

        /* Live jobs survive a reload so a dispatch minimized when the page was
           refreshed is still tracked (and dismissible). */
        var STORE = 'quot.dispatch.live';
        function persist() {
            if (!state.job || state.job.status !== 'running') { try { sessionStorage.removeItem(STORE); } catch (e) {} return; }
            try { sessionStorage.setItem(STORE, JSON.stringify({ job: state.job, quotation: state.quotation })); } catch (e) {}
        }

        /* ---------- §3.2 smooth progress ----------
         * The backend only emits four stage jumps (22/48/74/100), so the bar is
         * eased toward each real target instead of snapping. A frame loop (never
         * a closing timer) drives it, and it stops the moment it arrives. */
        function easeTo(target) {
            state.target = target;
            if (state.raf) return;
            var step = function () {
                var diff = state.target - state.shown;
                if (Math.abs(diff) < 0.5) {
                    state.shown = state.target;
                    paint(state.shown);
                    state.raf = null;
                    return;
                }
                state.shown += diff * 0.12;
                paint(state.shown);
                state.raf = window.requestAnimationFrame(step);
            };
            state.raf = window.requestAnimationFrame(step);
        }

        function paint(pct) {
            var v = Math.max(0, Math.min(100, Math.round(pct)));
            el.fill.style.width = v + '%';
            el.pct.textContent = v + '%';
            el.pbar.setAttribute('aria-valuenow', String(v));
            el.dfill.style.width = v + '%';
            el.drpct.textContent = v + '%';
        }

        /* ---------- modal rendering ---------- */

        function renderSteps(job) {
            var i;
            var failed = job.status === 'failed';
            var cancelled = job.status === 'cancelled';
            var ok = job.status === 'succeeded';
            var activeIdx = -1;
            for (i = 0; i < STAGES.length; i++) { if (STAGES[i].key === job.stage) { activeIdx = i; break; } }
            if (activeIdx < 0) activeIdx = 0;

            for (i = 0; i < el.steps.length; i++) {
                var li = el.steps[i];
                li.className = 'qd-mpstep';
                if (ok || i < activeIdx) { li.className += ' is-done'; continue; }
                if (i === activeIdx) { li.className += failed ? ' is-fail' : (cancelled ? ' is-cancel' : ' is-active'); }
            }
        }

        function renderModal(job) {
            var running = job.status === 'running';
            var ok = job.status === 'succeeded';
            var failed = job.status === 'failed';
            var cancelled = job.status === 'cancelled';
            var terminal = !running;

            var ref = refOf(job);
            var email = job.recipient || '';

            /* §2.2 — the header always names the exact document. */
            el.headref.textContent = COPY.head + ' · ' + ref;
            el.sub.textContent = ref ? (ref + ' → ' + email) : email;

            el.scrim.className = 'qd-scrim' + (ok ? ' is-done' : '') + (failed ? ' is-fail' : (cancelled ? ' is-cancel' : ''));
            /* A user-initiated cancel is not a delivery failure — it gets its own
               heading, otherwise the frozen result reads as an error. */
            el.title.textContent = running ? COPY.sendingTitle
                : (ok ? COPY.okTitle : (cancelled ? COPY.cancelTitle : COPY.failTitle));

            /* §4.2 — a frozen result reports the final stage, not a spinner. */
            if (ok) { el.log.textContent = COPY.deliveredLog; }
            else if (failed) { el.log.textContent = fill(COPY.failSub, {}); }
            else if (cancelled) { el.log.textContent = COPY.cancelSub; }
            else { el.log.textContent = (STAGES[activeStageIndex(job)] || {}).label || ''; }

            renderSteps(job);
            easeTo(ok ? 100 : (job.percent || 0));

            /* Footer state matrix. Nothing is actionable until the job row exists. */
            var starting = !!job.starting;
            el.cancel.hidden = !(running && !starting);
            el.minimize.hidden = !(running && !starting);
            el.close.hidden = !(terminal && !starting);
            el.retry.hidden = !failed;
            el.note.hidden = !failed;
            if (failed) { el.reason.textContent = job.error_message || COPY.genericFail; }
            el.hint.hidden = !running;

            /* Header affordances: `–` only while live, `✕` cancelled while live. */
            el.min.hidden = !running;
            el.x.hidden = starting;
        }

        function activeStageIndex(job) {
            for (var i = 0; i < STAGES.length; i++) { if (STAGES[i].key === job.stage) return i; }
            return 0;
        }

        /* ---------- §3.3 dock rendering ---------- */

        function renderDock() {
            var job = state.job;
            if (!job) { el.dock.hidden = true; return; }

            var running = job.status === 'running';
            var ok = job.status === 'succeeded';
            var failed = job.status === 'failed';
            var cancelled = job.status === 'cancelled';

            /* The dock is the *minimized* surface only. Once the modal is back
               (or another dispatch is started from it) the dock would otherwise
               linger beside the modal as a stale duplicate of the same job. */
            if (!state.minimized) { el.dock.hidden = true; return; }

            var ref = refOf(job);
            var reason = job.error_message || COPY.genericFail;

            el.dock.className = 'qd-dock' + (ok ? ' is-ok' : '') + (failed ? ' is-fail' : (cancelled ? ' is-cancel' : ''));

            el.dt.textContent = ref ? (ref + ' → ' + (job.recipient || '')) : (job.recipient || '');
            el.ds.textContent = ok && job.quotation && job.quotation.emailed_at
                ? job.quotation.emailed_at
                : (failed ? reason : '');

            el.dspn.hidden = !running;
            el.db.hidden = !running;

            if (running) {
                el.drs.textContent = COPY.sendingLog + ' · ' + (STAGES[activeStageIndex(job)] || {}).label;
                el.dopen.hidden = false;
                el.dview.hidden = true;
                el.dretry.hidden = true;
                el.ddismiss.hidden = true;
            } else if (ok) {
                el.dic2.className = 'qd-dric2 is-ok';
                el.dic2svg.innerHTML = ICON_OK;
                el.drt.textContent = fill(COPY.okDock, { ref: ref });
                el.drs2.textContent = fill(COPY.bgOkToast, { ref: ref, email: job.recipient });
                el.dview.hidden = !job.quotation || !job.quotation.show_url;
                el.dretry.hidden = true;
                el.ddismiss.hidden = false;
                el.dopen.hidden = true;
            } else if (cancelled) {
                el.dic2.className = 'qd-dric2 is-cancel';
                el.dic2svg.innerHTML = ICON_CANCEL;
                el.drt.textContent = fill(COPY.cancelDock, { ref: ref });
                el.drs2.textContent = COPY.cancelSub;
                el.dview.hidden = true;
                el.dretry.hidden = false;
                el.ddismiss.hidden = false;
                el.dopen.hidden = true;
            } else {
                el.dic2.className = 'qd-dric2 is-bad';
                el.dic2svg.innerHTML = ICON_BAD;
                el.drt.textContent = fill(COPY.failDock, { ref: ref });
                el.drs2.textContent = reason;
                el.dview.hidden = true;
                el.dretry.hidden = !failed;
                el.ddismiss.hidden = false;
                el.dopen.hidden = true;
            }

            el.dres.hidden = running;
            el.dock.hidden = false;
        }

        function render(job, opts) {
            opts = opts || {};
            state.job = job;
            /* A poll update must never re-open a minimized dispatch, so an
               omitted `minimized` keeps the current value rather than clearing it. */
            state.minimized = (opts.minimized !== undefined) ? !!opts.minimized : state.minimized;

            if (!job) { el.scrim.hidden = true; el.dock.hidden = true; return; }

            renderModal(job);

            if (state.minimized) {
                /* Minimized means minimized: a job that finishes in the
                   background reports through the dock + toast instead of
                   yanking the modal back. */
                el.scrim.hidden = true;
            } else {
                el.scrim.hidden = false;
                /* Only an open/terminal transition may move focus — pulling it
                   to the primary button on every 900ms tick would fight the
                   user mid-read. */
                if (!opts.updating) {
                    if (job.starting) {
                        el.modal.focus();
                    } else {
                        var focus = primaryAction();
                        if (focus) focus.focus();
                    }
                }
            }
            renderDock();
        }

        /* The one control a frozen result offers, so focus always lands somewhere useful. */
        function primaryAction() {
            var job = state.job;
            if (!job || job.status === 'running') return el.min;
            if (job.status === 'failed') return el.retry;
            return el.close;
        }

        /* ---------- polling ---------- */

        function schedule(delay) {
            if (state.timer) { clearTimeout(state.timer); state.timer = null; }
            state.timer = setTimeout(poll, delay || 900);
        }

        function poll() {
            var job = state.job;
            if (!job || job.status !== 'running') return;
            request(job.status_url, 'GET').then(function (data) {
                state.job = data;
                persist();
                render(data, { updating: true });
                if (data.status === 'running') { schedule(900); return; }
                settle(data);
            }).catch(function () { schedule(2600); });
        }

        /* A finished job must leave the register, the workspace row and the chip
           telling the same story. Reload only when there is nothing to patch. */
        function settle(job) {
            syncRegister(job);

            var ref = refOf(job);
            if (job.status === 'succeeded') {
                if (state.minimized) {
                    toast('success', COPY.bgOkTitle, fill(COPY.bgOkToast, { ref: ref, email: job.recipient }));
                }
                /* A successful send leaves the workspace closed — the register
                   row now carries the delivered state and the user chose to send. */
                state.reopenId = null;
                return;
            }

            var reason = job.error_message || COPY.genericFail;
            if (state.minimized) {
                toast('error', COPY.bgFailTitle, fill(COPY.bgFailToast, { ref: ref, reason: reason }));
            }
            /* §4.3 — an open modal FREEZES on the failure and reports once when
               the user closes it. No toast here, or it would double up. */
            if (state.reopenId && !state.minimized) { reopenWorkspace(state.reopenId); state.reopenId = null; }
        }

        function syncRegister(job) {
            if (!job.quotation) return;
            var qid = job.quotation.id;
            var view = window.QUOT_VIEW ? window.QUOT_VIEW[qid] : null;

            if (view) {
                view.email_status = job.quotation.email_status;
                view.fail_reason = job.quotation.fail_reason;
                view.emailed = !!job.quotation.emailed;
                view.emailed_at = job.quotation.emailed_at;
                view.emailed_to = job.quotation.emailed_to;
                view.email_failed = job.status === 'failed' || job.quotation.email_status === 'failed';
                view.can.email_retry = view.can.email_retry || view.can.email;
                if (typeof window.quotOpenDispatchRow === 'function') window.quotOpenDispatchRow(view);
                var foot = document.getElementById('qv-foot');
                if (foot && typeof window.quotFoot === 'function' && window.quotFoot(view)) foot.innerHTML = window.quotFoot(view);
                var row = document.querySelector('tr[data-qid="' + qid + '"] .q2-emchip');
                if (view.email_failed && !row) patchRowChip(qid, @json(__('Email failed')));
                if (!view.email_failed && row) row.remove();
                return;
            }

            var rowChip = document.querySelector('tr[data-qid="' + qid + '"] .q2-emchip');
            if (view === null && !rowChip && !document.querySelector('tr[data-qid="' + qid + '"]')) {
                window.location.reload();
                return;
            }
            if (view === null) { if (job.status === 'failed') patchRowChip(qid, @json(__('Email failed'))); else if (rowChip) rowChip.remove(); }
        }

        function patchRowChip(qid, label) {
            var tr = document.querySelector('tr[data-qid="' + qid + '"]');
            if (!tr) return;
            /* The Status cell is targeted by class, NOT by column index — the
               register's last column is Actions, so a positional lookup would
               drop the chip into the action buttons. */
            var cell = tr.querySelector('.q2-statcell') || tr.children[5];
            if (!cell) return;
            var chip = document.createElement('span');
            chip.className = 'q2-mchip mono q2-mchip--fail q2-emchip';
            chip.textContent = label;
            cell.appendChild(chip);
        }

        /* ---------- requests ---------- */

        function request(url, method, body) {
            var opt = {
                method: method || 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                credentials: 'same-origin'
            };
            if (body) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
            return fetch(url, opt).then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    if (!r.ok) {
                        var err = new Error(data.message || COPY.genericFail);
                        err.data = data; err.status = r.status;
                        throw err;
                    }
                    return data;
                });
            });
        }

        /* ---------- public surface ---------- */

        function start(url, quotation, isRetry) {
            state.quotation = quotation;
            /* A new dispatch clears any stale dock left by a previous job. */
            state.minimized = false;
            state.shown = 0;
            el.dock.hidden = true;
            try { sessionStorage.removeItem(STORE); } catch (e) {}

            /* Building the PDF runs server-side inside the POST and can take
               seconds, so paint the four stages straight away. Without this the
               user sits on a blank screen between "Send now" and the first poll,
               which reads as a dead click. */
            render(startingJob(quotation), { starting: true });

            return request(url, 'POST', {}).then(function (job) {
                state.job = job;
                persist();
                render(job);
                schedule(120);
                return job;
            }).catch(function (e) {
                toast('error', COPY.failTitle, e.message || COPY.genericFail);
                render(null);
                /* The send never happened, so put the user back where they were. */
                var back = state.reopenId;
                state.reopenId = null;
                state.job = null;
                if (back) reopenWorkspace(back);
                else if (state.lastFocus && state.lastFocus.focus) state.lastFocus.focus();
                throw e;
            });
        }

        /* The optimistic pre-POST view: running, stage 1, no job row yet. */
        function startingJob(d) {
            return {
                status: 'running',
                stage: STAGES[0].key,
                percent: 0,
                starting: true,
                error_message: null,
                cancel_url: null,
                recipient: (d && (d.emailed_to || d.email)) || '',
                quotation: d ? {
                    id: d.id,
                    number: d.number,
                    customer: d.customer || null,
                    show_url: (d.urls && d.urls.show) || null
                } : null
            };
        }

        function ask(d, reopenId) {
            if (!d) return;
            if (!d.email_ready) { toast('error', COPY.failTitle, COPY.noRecipient); return; }

            state.reopenId = reopenId || null;
            /* Remember where focus came from BEFORE the confirm dialog takes it,
               so the workspace can be handed back with focus restored. */
            state.lastFocus = document.activeElement;
            var isRetry = !!(d.can && d.can.email_retry && d.email_failed);
            var url = isRetry ? d.urls.email_retry : d.urls.email;

            window.CB.confirm({
                title: fill(COPY.askTitle, { ref: d.number, email: d.emailed_to || d.email }),
                message: COPY.askMessage,
                type: 'action',
                confirmLabel: COPY.askConfirm,
                cancelLabel: COPY.askCancel
            }).then(function (ok) {
                if (!ok) {
                    /* Nothing was sent — put the user back where they were. */
                    var back = state.reopenId;
                    state.reopenId = null;
                    reopenWorkspace(back);
                    return;
                }
                start(url, d, isRetry);
            });
        }

        function open(d) {
            if (!d) return;
            if (!d.email_ready) { toast('error', COPY.failTitle, COPY.noRecipient); return; }
            state.lastFocus = document.activeElement;
            var isRetry = !!(d.can && d.can.email_retry && d.email_failed);
            start(isRetry ? d.urls.email_retry : d.urls.email, d, isRetry);
        }

        function cancel() {
            var job = state.job;
            if (!job || job.status !== 'running' || state.busy) return;
            /* Before the POST returns there is no job row to cancel, and every
               close affordance (X, Esc, backdrop) routes through here. */
            if (job.starting) return;
            state.busy = true;
            request(job.cancel_url, 'POST', {}).then(function (data) {
                state.job = data;
                render(data);
                toast('info', COPY.cancelTitle, COPY.cancelToast);
                try { sessionStorage.removeItem(STORE); } catch (e) {}
                /* A cancelled send changed nothing, so the workspace comes straight back. */
                if (state.reopenId && !state.minimized) { reopenWorkspace(state.reopenId); }
                state.reopenId = null;
            }).catch(function () { /* the job may have completed first — poll resolves it */ })
            .then(function () { state.busy = false; schedule(400); });
        }

        function minimize() {
            if (!state.job || state.job.status !== 'running') return;
            state.minimized = true;
            persist();
            el.scrim.hidden = true;
            renderDock();
            toast('info', COPY.minTitle, COPY.minToast);
        }

        function restore() {
            if (!state.job) return;
            if (state.job.status === 'failed') { retry(); return; }
            state.minimized = false;
            render(state.job);
        }

        function retry() {
            var d = state.quotation;
            state.minimized = false;
            if (!d || !d.urls) return;
            start(d.urls.email_retry || d.urls.email, d, true);
        }

        /* §4.5 — closing a FINISHED modal: success is silent, failure reports
           once. Closing a live modal cancels instead (handled by `close`). */
        function closeFinished() {
            var job = state.job;
            var failed = job && (job.status === 'failed');
            var reason = (job && job.error_message) ? job.error_message : COPY.genericFail;
            clear();
            if (failed) { toast('error', COPY.failTitle, fill(COPY.closeFailToast, { reason: reason })); }
            restoreFocus();
        }

        function clear() {
            state.job = null;
            state.quotation = null;
            state.minimized = false;
            state.reopenId = null;
            state.shown = 0;
            state.target = 0;
            try { sessionStorage.removeItem(STORE); } catch (e) {}
            el.scrim.hidden = true;
            el.scrim.className = 'qd-scrim';
            el.dock.hidden = true;
            el.dock.className = 'qd-dock';
        }

        function restoreFocus() {
            if (state.lastFocus && state.lastFocus.focus) state.lastFocus.focus();
        }

        function done() {
            var job = state.job;
            if (job && job.status === 'running') return;
            clear();
            restoreFocus();
        }

        function dismiss() {
            var job = state.job;
            clear();
            if (job && job.status === 'failed' && typeof window.quotOpenDispatchRow === 'function' && window.QUOT_VIEW && job.quotation) {
                var v = window.QUOT_VIEW[job.quotation.id];
                if (v) window.quotOpenDispatchRow(v);
            }
        }

        function viewQuotation() {
            var job = state.job;
            if (job && job.quotation && job.quotation.show_url) {
                clear();
                window.location.href = job.quotation.show_url;
            }
        }

        /* ---------- wiring ---------- */

        window.QDispatch = { ask: ask, open: open, cancel: cancel, minimize: minimize, restore: restore, retry: retry, done: done, close: closeFinished, dismiss: dismiss, view: viewQuotation };

        function boot() {
            el.scrim = $('qd-scrim'); el.modal = $('qd-modal');
            el.headref = $('qd-headref'); el.title = $('qd-title'); el.sub = $('qd-sub');
            el.min = $('qd-min'); el.x = $('qd-x');
            el.pbar = $('qd-pbar'); el.fill = $('qd-fill'); el.pct = $('qd-pct'); el.log = $('qd-log');
            el.steps = document.querySelectorAll('#qd-modal .qd-mpstep');
            el.hint = $('qd-hint'); el.note = $('qd-note'); el.reason = $('qd-reason');
            el.cancel = $('qd-cancel'); el.minimize = $('qd-minimize'); el.close = $('qd-close'); el.retry = $('qd-retry');

            el.dock = $('qd-dock'); el.dic2 = $('qd-dric2'); el.dic2svg = $('qd-dric2svg');
            el.dt = $('qd-dt'); el.ds = $('qd-ds'); el.dspn = $('qd-dspn');
            el.dfill = $('qd-dfill'); el.drpct = $('qd-drpct'); el.drs = $('qd-drs');
            el.dres = $('qd-dres'); el.drt = $('qd-drt'); el.drs2 = $('qd-drs2'); el.db = $('qd-db');
            el.dopen = $('qd-dopen'); el.dview = $('qd-dview'); el.dretry = $('qd-dretry'); el.ddismiss = $('qd-ddismiss');

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-qd]');
                if (!btn) return;
                var act = btn.getAttribute('data-qd');
                if (act === 'minimize') minimize();
                else if (act === 'cancel') cancel();
                else if (act === 'retry') retry();
                else if (act === 'close') { if (state.job && state.job.status === 'running') cancel(); else closeFinished(); }
                else if (act === 'view') viewQuotation();
                else if (act === 'dismiss') dismiss();
                else if (act === 'restore') restore();
            });

            /* §6.1 — Esc closes the TOP-most layer only: the dispatch modal always
               wins, then the CB confirm, then the workspace. Because this
               listener is registered on capture it runs before any workspace
               handler. */
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                if (el.scrim.hidden) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                if (state.job && state.job.status === 'running') cancel();
                else closeFinished();
            }, true);

            el.scrim.addEventListener('mousedown', function (e) {
                if (e.target === el.scrim) { if (state.job && state.job.status === 'running') cancel(); else closeFinished(); }
            });

            /* Re-attach a dispatch that was still running when the page reloaded. */
            try {
                var raw = sessionStorage.getItem(STORE);
                if (raw) {
                    var saved = JSON.parse(raw);
                    if (saved && saved.job && saved.job.status === 'running') {
                        state.job = saved.job;
                        state.quotation = saved.quotation;
                        state.minimized = true;
                        render(state.job);
                        schedule(300);
                    }
                }
            } catch (e) {}
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
        else boot();
    })();
</script>