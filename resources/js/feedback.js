/**
 * Dialog system — Executive Teal. Single source of truth for dialogs, form
 * modals, processing overlays and toasts across the app.
 *
 * Primary API (window.CB):
 *   CB.confirm(config)             -> Promise<boolean>
 *       { type: 'danger'|'action', title, message, chip, context,
 *         confirmLabel, cancelLabel }
 *   CB.dialog(config)              -> Promise<void>
 *       { type: 'success'|'warning'|'info', title, message, chip, context,
 *         okLabel }
 *   CB.prompt(config)              -> Promise<string|null>  (form modal, one field)
 *       { title, message, label, placeholder, confirmLabel }
 *   CB.modal.open(el|id[, opts])   -> void   (generic form-modal host)
 *   CB.modal.close()               -> void
 *   CB.toast(type, title, message?, opts?)  (type: success|error|warning|info|system)
 *       opts: { duration, action: { label, onClick } }
 *   CB.busy(label?)  /  CB.busyStop()       (processing overlay)
 *
 * Legacy compatibility entry points (preserved — delegate to CB):
 *   window.feedback.{ toast, openConfirm, openPrompt, alert, confirm, prompt,
 *                     initFlashes }
 *   window.fbConfirmSubmit(event, message, opts)
 *   window.fbConfirmButton(event, message, opts)
 *   window.fbPromptForm(event, message, opts)
 *   window.fbConfirmOnly(event, message, opts)
 *   window.atlasToast(message, type)
 *
 * z-index map (spec): nav >= 60 · sticky heads 40 · scrim 80 · dialogs 85 · toasts 95
 */
(function () {
    'use strict';

    var TOAST_DURATION = { success: 4000, info: 4000, warning: 6000, system: 6000, error: 0 };
    var MAX_TOASTS = 4;

    /* ---- icons (teal set) ---- */
    var ICON_X = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
    var ICON_X_SM = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';

    var HALO = {
        danger: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        action: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 19V6M6 12l6-6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4.5 12.5l4.5 4.5L19.5 6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 4L2.5 20h19L12 4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        form: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    };

    var TOAST_ICONS = {
        success: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4.5 12.5l4.5 4.5L19.5 6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        error: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        warning: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 4L2.5 20h19L12 4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 10v4M12 17h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        info: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        system: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    };

    var viewport = null;
    var root = null;
    var lastFocused = null;
    var busyDepth = 0;
    var modalOpen = null;

    function esc(html) {
        var d = document.createElement('div');
        d.textContent = String(html == null ? '' : html);
        return d.innerHTML;
    }

    function ensureViewport() {
        viewport = document.getElementById('feedback-toasts');
        if (!viewport) {
            viewport = document.createElement('div');
            viewport.id = 'feedback-toasts';
            viewport.setAttribute('aria-live', 'polite');
            viewport.setAttribute('aria-relevant', 'additions');
            viewport.className = 'cb-toast-viewport';
            document.body.appendChild(viewport);
        }
        return viewport;
    }

    function ensureRoot() {
        root = document.getElementById('feedback-confirm-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'feedback-confirm-root';
            document.body.appendChild(root);
        }
        return root;
    }

    function focusables(el) {
        var selector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.prototype.slice.call(el.querySelectorAll(selector)).filter(function (node) { return node.offsetParent !== null || node === document.activeElement; });
    }

    function lockBody(lock) {
        document.body.classList.toggle('overflow-y-hidden', lock);
    }

    /* ---- toast ---- */

    function toast(type, title, message, opts) {
        opts = opts || {};
        if (typeof title === 'object' && title !== null && !message) {
            opts = title;
            message = opts.message;
            title = opts.title;
        }
        type = type || 'info';
        var vp = ensureViewport();
        var t = document.createElement('div');
        var typeClass = type === 'success' ? 'ok' : type === 'error' ? 'err' : type === 'warning' ? 'wrn' : 'inf';
        t.className = 'cb-toast cb-toast--' + typeClass;
        t.setAttribute('role', type === 'error' ? 'alert' : 'status');
        t.innerHTML = '<span class="cb-toast__icon">' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '</span>'
            + '<span class="cb-toast__content">'
            + (title ? '<span class="cb-toast__title">' + esc(title) + '</span>' : '')
            + (message ? '<span class="cb-toast__msg">' + esc(message) + '</span>' : '')
            + '</span>'
            + '<button type="button" class="cb-toast__close" aria-label="Dismiss">' + ICON_X_SM + '</button>';

        var duration = (opts.duration != null) ? opts.duration : (TOAST_DURATION[type] || 4000);
        var remaining = duration;
        var start = null;
        var rafId = null;
        var dismissed = false;

        function dismiss() {
            if (dismissed) return;
            dismissed = true;
            cancelAnimationFrame(rafId);
            t.classList.add('cb-toast--leaving');
            var cleanup = function () {
                if (t.parentNode) t.parentNode.removeChild(t);
                prune();
            };
            t.addEventListener('animationend', cleanup, { once: true });
            setTimeout(cleanup, 320);
        }

        function startBar() {
            if (duration <= 0) return;
            start = performance.now();
            cancelAnimationFrame(rafId);
            function tick(now) {
                if (dismissed) return;
                if (now - start >= remaining) { dismiss(); return; }
                rafId = requestAnimationFrame(tick);
            }
            rafId = requestAnimationFrame(tick);
        }
        function pauseBar() {
            if (dismissed || duration <= 0 || start === null) return;
            remaining -= (performance.now() - start);
            cancelAnimationFrame(rafId);
        }
        function resumeBar() {
            if (dismissed || duration <= 0) return;
            startBar();
        }

        t.addEventListener('mouseenter', pauseBar);
        t.addEventListener('mouseleave', resumeBar);
        t.querySelector('.cb-toast__close').addEventListener('click', dismiss);
        if (opts.action && opts.action.label) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cb-toast__action';
            btn.textContent = opts.action.label;
            btn.addEventListener('click', function () {
                try { opts.action.onClick && opts.action.onClick(); } finally { dismiss(); }
            });
            t.querySelector('.cb-toast__content').appendChild(btn);
        }

        vp.appendChild(t);
        prune();
        startBar();
    }

    function prune() {
        var vp = ensureViewport();
        var toasts = vp.querySelectorAll('.cb-toast');
        while (toasts.length > MAX_TOASTS) {
            var oldest = toasts[0];
            oldest.classList.add('cb-toast--leaving');
            (function (node) {
                setTimeout(function () {
                    if (node.parentNode) node.parentNode.removeChild(node);
                }, 320);
            })(oldest);
            toasts = vp.querySelectorAll('.cb-toast');
        }
    }

    /* ---- shared dialog shell ---- */

    function mountDialog(innerHtml, dialogClass, labelledBy) {
        var r = ensureRoot();
        r.querySelectorAll('.cb-scrim--leaving').forEach(function (el) {
            if (el.parentNode) el.parentNode.removeChild(el);
        });
        if (r.querySelector('.cb-dialog:not(.cb-dialog--processing)')) return null;
        var wrap = document.createElement('div');
        wrap.className = 'cb-scrim';
        wrap.innerHTML = '<div class="cb-dialog ' + (dialogClass || '') + '" role="alertdialog" aria-modal="true" aria-labelledby="' + labelledBy + '">'
            + innerHtml
            + '</div>';
        r.appendChild(wrap);
        lastFocused = document.activeElement;
        lockBody(true);
        return {
            wrap: wrap,
            modal: wrap.querySelector('.cb-dialog'),
            close: function (finish) {
                if (!wrap.isConnected) return;
                lockBody(false);
                wrap.classList.add('cb-scrim--leaving');
                wrap.querySelector('.cb-dialog').classList.add('cb-dialog--leaving');
                var done = function () {
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
                    if (lastFocused) {
                        try { lastFocused.focus(); } catch (e) {}
                        lastFocused = null;
                    }
                };
                wrap.addEventListener('animationend', done, { once: true });
                setTimeout(done, 180);
                if (finish) finish();
            },
            trap: function (e) {
                if (e.key === 'Tab') {
                    var f = focusables(wrap.querySelector('.cb-dialog'));
                    if (!f.length) return;
                    var first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            },
        };
    }

    function factStrip(chip, context) {
        if (!chip && !context) return '';
        return '<div class="cb-fact">'
            + (chip ? '<span class="cb-fact__chip">' + esc(chip) + '</span>' : '')
            + (context ? '<span class="cb-fact__text">' + esc(context) + '</span>' : '')
            + '</div>';
    }

    /* ---- CB.confirm ---- */

    function confirm(config) {
        config = config || {};
        var type = config.type === 'danger' ? 'danger' : 'action';
        return new Promise(function (resolve) {
            var m = mountDialog(
                '<button type="button" class="cb-dialog__close" data-cb-close aria-label="Close">' + ICON_X + '</button>'
                + '<div class="cb-halo cb-halo--' + type + '">' + (config.icon || HALO[type]) + '</div>'
                + '<h3 id="cb-dialog-title" class="cb-dialog__title">' + esc(config.title || '') + '</h3>'
                + (config.message ? '<p class="cb-dialog__sub">' + esc(config.message) + '</p>' : '')
                + (config.sub && !config.message ? '<p class="cb-dialog__sub">' + esc(config.sub) + '</p>' : '')
                + factStrip(config.chip, config.context)
                + summaryHtml(config.summary)
                + (config.typeToConfirm ? typeField(config.typeToConfirm) : '')
                + '<div class="cb-actions">'
                + '<button type="button" class="cb-btn cb-btn--ghost" data-cb-cancel>' + esc(config.cancelLabel || 'Cancel') + '</button>'
                + '<button type="button" class="cb-btn ' + (type === 'danger' ? 'cb-btn--red' : 'cb-btn--cta') + '" data-cb-ok disabled>' + esc(config.confirmLabel || (type === 'danger' ? 'Confirm' : 'Continue')) + '</button>'
                + '</div>',
                'cb-dialog--' + type,
                'cb-dialog-title'
            );
            if (!m) return;

            var okBtn = m.modal.querySelector('[data-cb-ok]');
            var typeInput = m.modal.querySelector('#cb-type-input');
            var cancelled = false;

            if (!config.typeToConfirm) okBtn.disabled = false;

            function finish(value) {
                m.close(function () { resolve(value); });
            }
            function cancel() {
                if (cancelled) return;
                cancelled = true;
                finish(false);
            }

            if (typeInput) {
                typeInput.addEventListener('input', function () {
                    okBtn.disabled = typeInput.value.trim() !== String(config.typeToConfirm);
                });
                typeInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !okBtn.disabled) { e.preventDefault(); okBtn.click(); }
                });
            }

            m.modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); cancel(); return; }
                m.trap(e);
            });
            m.wrap.addEventListener('click', function (e) {
                if (e.target === m.wrap) cancel();
            });
            m.modal.querySelector('[data-cb-close]').addEventListener('click', cancel);
            m.modal.querySelector('[data-cb-cancel]').addEventListener('click', cancel);
            okBtn.addEventListener('click', function () { finish(true); });

            var initial = (config.confirmFocus && !config.typeToConfirm) ? okBtn : (typeInput || m.modal.querySelector('[data-cb-cancel]'));
            setTimeout(function () { try { initial.focus(); } catch (e) {} }, 50);
        });
    }

    function summaryHtml(summary) {
        if (!summary || !summary.length) return '';
        return '<div class="cb-summary">'
            + summary.map(function (row) {
                return '<div class="cb-summary__row"><span class="cb-summary__k">' + esc(row.k) + '</span><span class="cb-summary__v">' + esc(row.v) + '</span></div>';
            }).join('')
            + '</div>';
    }

    function typeField(text) {
        return '<div class="cb-field" style="margin-top:16px">'
            + '<label for="cb-type-input">Type <strong>' + esc(text) + '</strong> to confirm</label>'
            + '<input id="cb-type-input" class="cb-input" type="text" autocomplete="off" spellcheck="false" placeholder="' + esc(text) + '">'
            + '</div>';
    }

    /* ---- CB.dialog (success / warning / info) ---- */

    function dialog(config) {
        config = config || {};
        var type = (config.type === 'warning' || config.type === 'info') ? config.type : 'success';
        var btnClass = type === 'success' ? 'cb-btn--sec' : type === 'warning' ? 'cb-btn--warn' : 'cb-btn--cta';
        var okLabel = config.okLabel || (type === 'success' ? 'Done' : type === 'warning' ? 'Continue' : 'Got it');
        var closeBtn = type === 'success' ? '' : '<button type="button" class="cb-dialog__close" data-cb-close aria-label="Close">' + ICON_X + '</button>';
        return new Promise(function (resolve) {
            var m = mountDialog(
                closeBtn
                + '<div class="cb-halo cb-halo--' + type + '">' + (config.icon || HALO[type]) + '</div>'
                + '<h3 id="cb-dialog-title" class="cb-dialog__title">' + esc(config.title || '') + '</h3>'
                + (config.message ? '<p class="cb-dialog__sub">' + esc(config.message) + '</p>' : '')
                + factStrip(config.chip, config.context)
                + '<div class="cb-actions">'
                + '<button type="button" class="cb-btn ' + btnClass + '" data-cb-ok>' + esc(okLabel) + '</button>'
                + '</div>',
                'cb-dialog--' + type,
                'cb-dialog-title'
            );
            if (!m) return;

            function finish() { m.close(function () { resolve(); }); }
            m.modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); finish(); return; }
                m.trap(e);
            });
            m.wrap.addEventListener('click', function (e) { if (e.target === m.wrap) finish(); });
            var closeBtnEl = m.modal.querySelector('[data-cb-close]');
            if (closeBtnEl) closeBtnEl.addEventListener('click', finish);
            m.modal.querySelector('[data-cb-ok]').addEventListener('click', finish);
            setTimeout(function () { try { m.modal.querySelector('[data-cb-ok]').focus(); } catch (e) {} }, 50);
        });
    }

    /* ---- CB.prompt (form modal, single field) ---- */

    function prompt(config) {
        config = config || {};
        return new Promise(function (resolve) {
            var m = mountDialog(
                '<button type="button" class="cb-dialog__close" data-cb-close aria-label="Close">' + ICON_X + '</button>'
                + '<div class="cb-halo cb-halo--action">' + HALO.form + '</div>'
                + '<h3 id="cb-dialog-title" class="cb-dialog__title">' + esc(config.title || '') + '</h3>'
                + (config.message ? '<p class="cb-dialog__sub">' + esc(config.message) + '</p>' : '')
                + '<div class="cb-field" style="margin-top:16px">'
                + '<label for="cb-prompt-input">' + esc(config.label || 'Reason') + '</label>'
                + '<input id="cb-prompt-input" class="cb-input" type="text" autocomplete="off" placeholder="' + esc(config.placeholder || '') + '">'
                + '</div>'
                + '<div class="cb-actions">'
                + '<button type="button" class="cb-btn cb-btn--ghost" data-cb-cancel>' + esc(config.cancelLabel || 'Cancel') + '</button>'
                + '<button type="button" class="cb-btn cb-btn--cta" data-cb-ok>' + esc(config.confirmLabel || 'Confirm') + '</button>'
                + '</div>',
                'cb-dialog--wide cb-dialog--form',
                'cb-dialog-title'
            );
            if (!m) return;

            var input = m.modal.querySelector('#cb-prompt-input');

            function finish(value) { m.close(function () { resolve(value); }); }
            function cancel() { finish(null); }

            m.modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); cancel(); return; }
                m.trap(e);
            });
            m.wrap.addEventListener('click', function (e) { if (e.target === m.wrap) cancel(); });
            m.modal.querySelector('[data-cb-close]').addEventListener('click', cancel);
            m.modal.querySelector('[data-cb-cancel]').addEventListener('click', cancel);
            m.modal.querySelector('[data-cb-ok]').addEventListener('click', function () { finish(input.value); });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); finish(input.value); }
            });
            setTimeout(function () { try { input.focus(); } catch (e) {} }, 50);
        });
    }

    /* ---- CB.modal (generic form-modal host) ---- */

    var modalApi = {
        open: function (elOrId, opts) {
            opts = opts || {};
            var el = (typeof elOrId === 'string') ? document.getElementById(elOrId) : elOrId;
            if (!el) return;
            var r = ensureRoot();
            if (r.querySelector('.cb-dialog') || modalOpen) return;

            var wrap = document.createElement('div');
            wrap.className = 'cb-scrim';
            var dlg = document.createElement('div');
            dlg.className = 'cb-dialog cb-dialog--wide';
            dlg.setAttribute('role', 'dialog');
            dlg.setAttribute('aria-modal', 'true');
            dlg.appendChild(el);
            wrap.appendChild(dlg);
            r.appendChild(wrap);

            var origin = el.parentNode;
            var nextSib = el.nextSibling;
            modalOpen = { el: el, origin: origin, next: nextSib, wrap: wrap, dlg: dlg, onClose: opts.onClose || null };
            lastFocused = document.activeElement;
            lockBody(true);

            dlg.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); modalApi.close(); return; }
                var f = focusables(dlg);
                if (!f.length) return;
                var first = f[0], last = f[f.length - 1];
                if (e.key === 'Tab') {
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
            wrap.addEventListener('click', function (e) { if (e.target === wrap) modalApi.close(); });
        },
        close: function () {
            if (!modalOpen) return;
            var state = modalOpen;
            modalOpen = null;
            lockBody(false);
            state.wrap.classList.add('cb-scrim--leaving');
            state.dlg.classList.add('cb-dialog--leaving');
            var done = function () {
                if (state.wrap.parentNode) state.wrap.parentNode.removeChild(state.wrap);
                if (state.origin) state.origin.insertBefore(state.el, state.next);
                if (lastFocused) { try { lastFocused.focus(); } catch (e) {} lastFocused = null; }
                if (state.onClose) state.onClose();
            };
            state.wrap.addEventListener('animationend', done, { once: true });
            setTimeout(done, 180);
        },
    };

    /* ---- CB.busy / CB.busyStop (processing overlay) ---- */

    function busy(label) {
        var r = ensureRoot();
        busyDepth += 1;
        if (r.querySelector('.cb-dialog--processing')) return;
        var wrap = document.createElement('div');
        wrap.className = 'cb-scrim cb-scrim--processing';
        wrap.innerHTML = '<div class="cb-dialog cb-dialog--processing" role="status" aria-live="polite">'
            + '<div class="cb-spinner"></div>'
            + '<h3 class="cb-dialog__title" style="font-size:15px">' + esc(label || 'Processing…') + '</h3>'
            + '<p class="cb-dialog__sub" style="margin-top:6px">Please wait…</p>'
            + '</div>';
        r.appendChild(wrap);
    }

    function busyStop() {
        busyDepth = Math.max(0, busyDepth - 1);
        if (busyDepth !== 0) return;
        var r = ensureRoot();
        var wrap = r.querySelector('.cb-scrim--processing');
        if (!wrap) return;
        wrap.classList.add('cb-scrim--leaving');
        wrap.querySelector('.cb-dialog').classList.add('cb-dialog--leaving');
        var done = function () {
            if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
        };
        wrap.addEventListener('animationend', done, { once: true });
        setTimeout(done, 180);
    }

    /* ---- legacy inline-handler helpers ---- */

    function fbConfirmSubmit(event, message, opts) {
        event.preventDefault();
        var form = event.target;
        opts = opts || {};
        confirm(Object.assign({ type: opts.type || 'action', title: message }, opts)).then(function (ok) {
            if (ok) form.submit();
        });
        return false;
    }

    function fbConfirmButton(event, message, opts) {
        event.preventDefault();
        var btn = event.currentTarget;
        var form = btn.form;
        opts = opts || {};
        confirm(Object.assign({ type: opts.type || 'action', title: message }, opts)).then(function (ok) {
            if (!ok || !form) return;
            if (btn.name) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = btn.name;
                hidden.value = btn.value || '1';
                form.appendChild(hidden);
            }
            form.submit();
        });
        return false;
    }

    function fbPromptForm(event, message, opts) {
        event.preventDefault();
        var form = event.target;
        prompt(Object.assign({ title: message }, opts || {})).then(function (reason) {
            if (reason === null) return;
            var field = form.querySelector('[name="void_reason"]') || form.querySelector('[name="reason"]');
            if (field) field.value = reason;
            form.submit();
        });
        return false;
    }

    function fbConfirmOnly(event, message, opts) {
        event.preventDefault();
        opts = opts || {};
        confirm(Object.assign({ type: opts.type || 'action', title: message }, opts));
        return false;
    }

    /* ---- legacy window.feedback API ---- */

    function openConfirm(config) {
        config = config || {};
        var type = config.variant === 'danger' ? 'danger' : 'action';
        return confirm(Object.assign({}, config, { type: type }));
    }

    function openPrompt(config) {
        return prompt(config || {});
    }

    var api = {
        toast: toast,
        openConfirm: openConfirm,
        openPrompt: openPrompt,
        alert: function (message) { toast('error', message); },
        confirm: function (message, opts) { return confirm(Object.assign({ type: (opts && opts.type) || 'action', title: message }, opts || {})); },
        prompt: function (message, opts) { return prompt(Object.assign({ title: message }, opts || {})); },
        initFlashes: function (flashes) {
            if (!flashes) return;
            if (flashes.success) toast('success', flashes.success);
            if (flashes.error) toast('error', flashes.error);
            if (flashes.warning) toast('warning', flashes.warning);
            if (flashes.info) toast('info', flashes.info);
            if (flashes.status && flashes.status !== '') toast('info', flashes.status);
        },
    };

    /* ---- exports ---- */

    window.CB = {
        confirm: confirm,
        dialog: dialog,
        prompt: prompt,
        modal: modalApi,
        toast: toast,
        busy: busy,
        busyStop: busyStop,
    };

    window.feedback = api;
    window.atlasToast = function (message, type) {
        toast(type || 'success', message);
    };

    window.fbConfirmSubmit = fbConfirmSubmit;
    window.fbConfirmButton = fbConfirmButton;
    window.fbPromptForm = fbPromptForm;
    window.fbConfirmOnly = fbConfirmOnly;

    // Fire flash messages embedded by <x-feedback.flashes /> on the page.
    function fireFlashes() {
        var el = document.getElementById('feedback-flashes');
        if (!el || !el.dataset || !el.dataset.flashes) return;
        try {
            window.feedback.initFlashes(JSON.parse(el.dataset.flashes));
        } catch (e) { /* noop */ }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireFlashes);
    } else {
        fireFlashes();
    }
})();
