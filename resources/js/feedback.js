/**
 * Centralised feedback system — toasts, inline alerts, system banner and
 * confirmation/prompt modals. Single source of truth for transient + modal
 * feedback across the app.
 *
 * Public API (window.feedback):
 *   toast(type, title, message?, opts?)
 *   openConfirm(config) -> Promise<boolean>
 *   openPrompt(config)   -> Promise<string|null>
 *   alert(message)            (alias: error toast)
 *   confirm(message, opts)    (alias: openConfirm, initial focus Confirm)
 *   prompt(message)           (alias: openPrompt)
 *
 * Legacy inline-handler helpers (global):
 *   fbConfirmSubmit(event, message, opts)   — for `onsubmit="return fbConfirmSubmit(event, '...')"`
 *   fbConfirmButton(event, message, opts)   — for submit buttons with `onclick="return fbConfirmButton(event, '...')"`
 *   fbPromptForm(event, message, opts)      — for forms collecting a reason via prompt()
 *   fbConfirmOnly(event, message, opts)     — for buttons that only confirm (no-op on resolve)
 */
(function () {
    'use strict';

    var TOAST_DURATION = { success: 5000, error: 8000, warning: 6000, info: 5000, system: 8000 };
    var MAX_TOASTS = 4;

    var ICONS = {
        success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        warning: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.3 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.7 3.86a2 2 0 00-3.4 0z"/></svg>',
        error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
        info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        system: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    };

    var CONFIRM_ICONS = {
        danger: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        approve: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        alert: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.3 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.7 3.86a2 2 0 00-3.4 0z"/></svg>',
    };

    var viewport = null;
    var confirmRoot = null;
    var lastFocused = null;
    var openModals = 0;

    function ensureViewport() {
        viewport = document.getElementById('feedback-toasts');
        if (!viewport) {
            viewport = document.createElement('div');
            viewport.id = 'feedback-toasts';
            viewport.setAttribute('aria-live', 'polite');
            viewport.className = 'fb-toast-viewport';
            document.body.appendChild(viewport);
        }
        return viewport;
    }

    function ensureConfirmRoot() {
        confirmRoot = document.getElementById('feedback-confirm-root');
        if (!confirmRoot) {
            confirmRoot = document.createElement('div');
            confirmRoot.id = 'feedback-confirm-root';
            document.body.appendChild(confirmRoot);
        }
        return confirmRoot;
    }

    function esc(html) {
        var d = document.createElement('div');
        d.textContent = String(html == null ? '' : html);
        return d.innerHTML;
    }

    /**
     * toast(type, title, message?, opts?)
     * type: success | error | warning | info | system
     * opts: { duration, action: { label, onClick } }
     */
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
        t.className = 'fb-toast fb-toast--' + type;
        t.setAttribute('role', type === 'error' ? 'alert' : 'status');
        var html = '<span class="fb-toast__icon">' + (ICONS[type] || ICONS.info) + '</span>'
            + '<span class="fb-toast__content">'
            + (title ? '<span class="fb-toast__title">' + esc(title) + '</span>' : '')
            + (message ? '<span class="fb-toast__msg">' + esc(message) + '</span>' : '')
            + '</span>'
            + '<button type="button" class="fb-toast__close" aria-label="Dismiss">&times;</button>'
            + '<span class="fb-toast__progress"><span></span></span>';
        t.innerHTML = html;

        var bar = t.querySelector('.fb-toast__progress span');
        var duration = opts.duration || TOAST_DURATION[type] || 5000;
        var remaining = duration;
        var start = null;
        var rafId = null;
        var dismissed = false;

        function setProgress(frac) {
            bar.style.width = Math.max(0, Math.min(100, frac * 100)) + '%';
        }

        function startBar() {
            start = performance.now();
            cancelAnimationFrame(rafId);
            function tick(now) {
                var elapsed = now - start;
                if (elapsed >= remaining) {
                    dismiss();
                    return;
                }
                setProgress(1 - elapsed / duration);
                rafId = requestAnimationFrame(tick);
            }
            rafId = requestAnimationFrame(tick);
        }

        function pauseBar() {
            if (dismissed || start === null) return;
            var now = performance.now();
            remaining -= (now - start);
            cancelAnimationFrame(rafId);
        }

        function resumeBar() {
            if (dismissed) return;
            startBar();
        }

        function dismiss() {
            if (dismissed) return;
            dismissed = true;
            cancelAnimationFrame(rafId);
            t.classList.add('fb-toast--leaving');
            t.addEventListener('animationend', function () {
                if (t.parentNode) t.parentNode.removeChild(t);
                prune();
            }, { once: true });
            setTimeout(function () {
                if (t.parentNode) t.parentNode.removeChild(t);
                prune();
            }, 320);
        }

        t.addEventListener('mouseenter', pauseBar);
        t.addEventListener('mouseleave', resumeBar);
        t.querySelector('.fb-toast__close').addEventListener('click', dismiss);
        if (opts.action && opts.action.label) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fb-toast__action';
            btn.textContent = opts.action.label;
            btn.addEventListener('click', function () {
                try { opts.action.onClick && opts.action.onClick(); } finally { dismiss(); }
            });
            t.querySelector('.fb-toast__content').appendChild(btn);
        }

        vp.appendChild(t);
        prune();
        setProgress(1);
        startBar();
    }

    function prune() {
        var vp = ensureViewport();
        var toasts = vp.querySelectorAll('.fb-toast');
        while (toasts.length > MAX_TOASTS) {
            var oldest = toasts[0];
            oldest.classList.add('fb-toast--leaving');
            var el = oldest;
            (function (node) {
                setTimeout(function () {
                    if (node.parentNode) node.parentNode.removeChild(node);
                }, 320);
            })(el);
            toasts = vp.querySelectorAll('.fb-toast');
        }
    }

    function focusables(root) {
        var selector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.prototype.slice.call(root.querySelectorAll(selector)).filter(function (el) { return el.offsetParent !== null || el === document.activeElement; });
    }

    function lockBody(lock) {
        document.body.classList.toggle('overflow-y-hidden', lock);
    }

    /**
     * openConfirm(config) -> Promise<boolean>
     * config: {
     *   title, message (HTML-safe plain text),
     *   confirmLabel, cancelLabel,
     *   variant: 'danger'|'navy'|'default',
     *   typeToConfirm: string (enables confirm once typed),
     *   summary: [{ k, v }],
     *   sub: string (navy-head sub-line),
     *   confirmFocus: boolean (initial focus Confirm instead of Cancel)
     * }
     */
    function openConfirm(config) {
        config = config || {};
        return new Promise(function (resolve) {
            var root = ensureConfirmRoot();
            if (root.querySelector('.fb-confirm')) return;

            var variant = config.variant || 'default';
            var confirmClass = variant === 'danger' ? 'fb-btn--danger' : 'fb-btn--primary';
            var icon = config.icon || (variant === 'danger' ? CONFIRM_ICONS.danger : CONFIRM_ICONS.approve);
            var confirmLabel = config.confirmLabel || 'Confirm';
            var cancelLabel = config.cancelLabel || 'Cancel';
            var typeToConfirm = config.typeToConfirm || '';

            var summaryHtml = '';
            if (config.summary && config.summary.length) {
                summaryHtml = '<div class="fb-confirm__summary">'
                    + config.summary.map(function (row) {
                        return '<div class="fb-confirm__summary-row"><span class="fb-confirm__summary-k">' + esc(row.k) + '</span><span class="fb-confirm__summary-v">' + esc(row.v) + '</span></div>';
                    }).join('')
                    + '</div>';
            }

            var typeField = '';
            if (typeToConfirm) {
                typeField = '<div class="fb-confirm__type">'
                    + '<label for="fb-confirm-type">Type <strong>' + esc(typeToConfirm) + '</strong> to confirm</label>'
                    + '<input id="fb-confirm-type" type="text" autocomplete="off" spellcheck="false" placeholder="' + esc(typeToConfirm) + '">'
                    + '</div>';
            }

            var head = variant === 'navy'
                ? '<div class="fb-confirm__head-panel">'
                    + '<span class="fb-confirm__head-panel-icon">' + icon + '</span>'
                    + '<span class="fb-confirm__head-panel-text">'
                        + '<span class="fb-confirm__head-panel-title">' + esc(config.title || '') + '</span>'
                        + (config.sub ? '<span class="fb-confirm__head-panel-sub">' + esc(config.sub) + '</span>' : '')
                    + '</span>'
                + '</div>'
                : '<div class="fb-confirm__head">'
                    + '<span class="fb-confirm__icon fb-confirm__icon--' + variant + '">' + icon + '</span>'
                    + '<span class="fb-confirm__text">'
                        + '<span class="fb-confirm__title">' + esc(config.title || '') + '</span>'
                        + (config.message ? '<span class="fb-confirm__body">' + esc(config.message) + '</span>' : '')
                    + '</span>'
                + '</div>';

            var wrap = document.createElement('div');
            wrap.className = 'fb-confirm-overlay';
            wrap.innerHTML = '<div class="fb-confirm fb-confirm--' + variant + '" role="dialog" aria-modal="true" aria-labelledby="fb-confirm-title">'
                + head
                + summaryHtml
                + typeField
                + '<div class="fb-confirm__actions">'
                    + '<button type="button" class="fb-btn fb-btn--cancel" data-fb-cancel>' + esc(cancelLabel) + '</button>'
                    + '<button type="button" class="fb-btn ' + confirmClass + '" data-fb-confirm disabled>' + esc(confirmLabel) + '</button>'
                + '</div>'
            + '</div>';
            root.appendChild(wrap);

            var modal = wrap.querySelector('.fb-confirm');
            var cancelBtn = wrap.querySelector('[data-fb-cancel]');
            var confirmBtn = wrap.querySelector('[data-fb-confirm]');
            var typeInput = wrap.querySelector('#fb-confirm-type');

            if (!typeToConfirm) confirmBtn.disabled = false;

            function resolveWith(value) {
                if (!wrap.isConnected) return;
                lockBody(false);
                modal.classList.add('fb-confirm--leaving');
                wrap.classList.add('fb-confirm-overlay--leaving');
                wrap.addEventListener('animationend', function () {
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
                }, { once: true });
                setTimeout(function () {
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
                    if (lastFocused) { try { lastFocused.focus(); } catch (e) {} lastFocused = null; }
                }, 180);
                openModals = Math.max(0, openModals - 1);
                resolve(value);
            }

            function cancel() { resolveWith(false); }

            lastFocused = document.activeElement;
            lockBody(true);
            openModals += 1;

            if (typeInput) {
                typeInput.addEventListener('input', function () {
                    confirmBtn.disabled = typeInput.value.trim() !== typeToConfirm;
                });
                typeInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !confirmBtn.disabled) { e.preventDefault(); confirmBtn.click(); }
                });
            }

            wrap.addEventListener('click', function (e) {
                if (e.target === wrap) cancel();
            });

            modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); cancel(); }
                if (e.key === 'Tab') {
                    var f = focusables(modal);
                    if (!f.length) return;
                    var first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
            document.addEventListener('keydown', onKeydown, true);

            function onKeydown(e) {
                if (e.key === 'Escape' && wrap.isConnected) {
                    e.stopPropagation();
                    cancel();
                    document.removeEventListener('keydown', onKeydown, true);
                }
            }
            wrap.addEventListener('fb-resolved', function () {
                document.removeEventListener('keydown', onKeydown, true);
            });

            cancelBtn.addEventListener('click', cancel);
            confirmBtn.addEventListener('click', function () {
                wrap.dispatchEvent(new CustomEvent('fb-resolved'));
                resolveWith(true);
            });

            var initial = (config.confirmFocus && !typeToConfirm) ? confirmBtn : (typeInput || cancelBtn);
            setTimeout(function () { try { initial.focus(); } catch (e) {} }, 50);
        });
    }

    /**
     * openPrompt(config) -> Promise<string|null>
     * config: { title, message, label, placeholder, confirmLabel }
     */
    function openPrompt(config) {
        config = config || {};
        return new Promise(function (resolve) {
            var root = ensureConfirmRoot();
            if (root.querySelector('.fb-confirm')) return;

            var label = config.label || 'Reason';
            var placeholder = config.placeholder || '';
            var confirmLabel = config.confirmLabel || 'Confirm';
            var cancelLabel = config.cancelLabel || 'Cancel';

            var wrap = document.createElement('div');
            wrap.className = 'fb-confirm-overlay';
            wrap.innerHTML = '<div class="fb-confirm fb-confirm--default" role="dialog" aria-modal="true" aria-labelledby="fb-confirm-title">'
                + '<div class="fb-confirm__head">'
                    + '<span class="fb-confirm__icon fb-confirm__icon--default">' + (ICONS.info || '') + '</span>'
                    + '<span class="fb-confirm__text">'
                        + '<span class="fb-confirm__title">' + esc(config.title || '') + '</span>'
                        + (config.message ? '<span class="fb-confirm__body">' + esc(config.message) + '</span>' : '')
                    + '</span>'
                + '</div>'
                + '<div class="fb-confirm__type">'
                    + '<label for="fb-prompt-input">' + esc(label) + '</label>'
                    + '<input id="fb-prompt-input" type="text" autocomplete="off" placeholder="' + esc(placeholder) + '">'
                + '</div>'
                + '<div class="fb-confirm__actions">'
                    + '<button type="button" class="fb-btn fb-btn--cancel" data-fb-cancel>' + esc(cancelLabel) + '</button>'
                    + '<button type="button" class="fb-btn fb-btn--primary" data-fb-confirm>' + esc(confirmLabel) + '</button>'
                + '</div>'
            + '</div>';
            root.appendChild(wrap);

            var modal = wrap.querySelector('.fb-confirm');
            var cancelBtn = wrap.querySelector('[data-fb-cancel]');
            var confirmBtn = wrap.querySelector('[data-fb-confirm]');
            var input = wrap.querySelector('#fb-prompt-input');

            function resolveWith(value) {
                if (!wrap.isConnected) return;
                lockBody(false);
                wrap.classList.add('fb-confirm-overlay--leaving');
                modal.classList.add('fb-confirm--leaving');
                wrap.addEventListener('animationend', function () {
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
                }, { once: true });
                setTimeout(function () {
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
                    if (lastFocused) { try { lastFocused.focus(); } catch (e) {} lastFocused = null; }
                }, 180);
                resolve(value);
            }
            function cancel() { resolveWith(null); }

            lastFocused = document.activeElement;
            lockBody(true);

            wrap.addEventListener('click', function (e) {
                if (e.target === wrap) cancel();
            });
            modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { e.stopPropagation(); cancel(); }
                if (e.key === 'Tab') {
                    var f = focusables(modal);
                    if (!f.length) return;
                    var first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); }
            });
            cancelBtn.addEventListener('click', cancel);
            confirmBtn.addEventListener('click', function () {
                resolveWith(input.value);
            });

            setTimeout(function () { try { input.focus(); } catch (e) {} }, 50);
        });
    }

    /* ---- legacy inline-handler helpers ---- */

    function fbConfirmSubmit(event, message, opts) {
        event.preventDefault();
        var form = event.target;
        openConfirm(Object.assign({ title: message }, opts || {})).then(function (ok) {
            if (ok) form.submit();
        });
        return false;
    }

    function fbConfirmButton(event, message, opts) {
        event.preventDefault();
        var btn = event.currentTarget;
        var form = btn.form;
        openConfirm(Object.assign({ title: message }, opts || {})).then(function (ok) {
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
        openPrompt(Object.assign({ title: message }, opts || {})).then(function (reason) {
            if (reason === null) return;
            var field = form.querySelector('[name="void_reason"]') || form.querySelector('[name="reason"]');
            if (field) field.value = reason;
            form.submit();
        });
        return false;
    }

    function fbConfirmOnly(event, message, opts) {
        event.preventDefault();
        openConfirm(Object.assign({ title: message }, opts || {}));
        return false;
    }

    /* ---- exports ---- */

    var api = {
        toast: toast,
        openConfirm: openConfirm,
        openPrompt: openPrompt,
        alert: function (message) { toast('error', message); },
        confirm: function (message, opts) { return openConfirm(Object.assign({ title: message }, opts || {})); },
        prompt: function (message, opts) { return openPrompt(Object.assign({ title: message }, opts || {})); },
    };

    window.feedback = api;
    window.atlasToast = function (message, type) {
        toast(type || 'success', message);
    };

    window.fbConfirmSubmit = fbConfirmSubmit;
    window.fbConfirmButton = fbConfirmButton;
    window.fbPromptForm = fbPromptForm;
    window.fbConfirmOnly = fbConfirmOnly;

    window.feedback.initFlashes = function (flashes) {
        if (!flashes) return;
        if (flashes.success) toast('success', flashes.success);
        if (flashes.error) toast('error', flashes.error);
        if (flashes.warning) toast('warning', flashes.warning);
        if (flashes.info) toast('info', flashes.info);
        if (flashes.status && flashes.status !== '') toast('info', flashes.status);
    };

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
