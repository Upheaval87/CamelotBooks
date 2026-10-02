/**
 * Journal entry detail — copy-to-clipboard + a safe keyboard print path.
 *
 * Design constraints:
 *  · The A4 voucher is SERVER-RENDERED on its own page
 *    (accounting.journal-entries.print). "Print Voucher" is a plain link with
 *    target="_blank", so it needs no JavaScript at all: the new tab is the live
 *    preview and it owns Print + Download PDF.
 *  · The old in-page overlay (#glj-print) and its cloned-iframe print pipeline
 *    were removed together with that overlay. The only thing that survives here
 *    is the copy button and the Ctrl/Cmd+P redirect, which now points at the
 *    same print page. That is deliberate: printing this document directly would
 *    print the detail screen's chrome, so the keyboard path must not fall
 *    through to the browser default here.
 */
(function () {
    'use strict';

    const APP_ID = 'glj-app';

    const app = () => document.getElementById(APP_ID);

    const toast = (type, title, message) => {
        if (window.CB && typeof window.CB.toast === 'function') {
            window.CB.toast(type, title, message);
        }
    };

    /** Open the standalone voucher page the toolbar links to. */
    function openPrintPage() {
        const link = document.querySelector('[data-glj-print-url]');

        if (!link) {
            return false;
        }

        const url = link.getAttribute('data-glj-print-url');

        if (!url) {
            return false;
        }

        /* Open first, then sever the opener by hand: passing "noopener" as a
         * window feature makes some browsers return null even though the tab did
         * open, which would raise a bogus "blocked" toast. */
        const win = window.open(url, '_blank');

        if (!win) {
            return false;
        }

        try {
            win.opener = null;
        } catch (e) {
            /* Cross-origin opener assignment is a no-op; the target is ours anyway. */
        }

        return true;
    }

    /* -- copy journal number ------------------------------------------------ */
    function copyText(text, button) {
        const done = () => {
            if (button) {
                const original = button.getAttribute('title');
                button.setAttribute('title', 'Copied');
                button.setAttribute('aria-label', 'Copied');
                window.setTimeout(() => {
                    button.setAttribute('title', original || 'Copy journal number');
                    button.setAttribute('aria-label', original || 'Copy journal number');
                }, 1400);
            }
            toast('success', 'Copied', text);
        };

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(done).catch(() => legacyCopy(text, done));

            return;
        }
        legacyCopy(text, done);
    }

    function legacyCopy(text, done) {
        try {
            const area = document.createElement('textarea');

            area.value = text;
            area.setAttribute('readonly', '');
            area.style.cssText = 'position:fixed;top:0;left:-9999px;opacity:0;';
            document.body.appendChild(area);
            area.select();
            const ok = document.execCommand('copy');

            document.body.removeChild(area);
            if (ok) {
                done();
            } else {
                toast('error', 'Could not copy', 'Copy the number manually: ' + text);
            }
        } catch (e) {
            toast('error', 'Could not copy', 'Copy the number manually: ' + text);
        }
    }

    /* -- wiring ------------------------------------------------------------- */
    function onClick(event) {
        const target = event.target;

        if (!target || typeof target.closest !== 'function') {
            return;
        }

        const copier = target.closest('[data-glj-copy]');
        if (copier) {
            event.preventDefault();
            copyText(copier.getAttribute('data-glj-copy') || '', copier);
        }
    }

    function onKeydown(event) {
        if (!(event.ctrlKey || event.metaKey) || (event.key !== 'p' && event.key !== 'P')) {
            return;
        }

        /* init() only wires this when #glj-app is present, so we are always on the
         * detail screen — never the print page. Printing that screen directly
         * would print the app chrome, so swallow the browser shortcut FIRST and
         * unconditionally, then try to hand off to the standalone voucher. */
        event.preventDefault();

        if (!openPrintPage()) {
            toast('error', 'Print tab blocked', 'Allow pop-ups for this site, or use the Print Voucher button.');
        }
    }

    function init() {
        if (!app()) {
            return;
        }
        document.addEventListener('click', onClick);
        document.addEventListener('keydown', onKeydown);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.JournalDetail = {
        openPrintPage: openPrintPage,
        copyText: copyText,
    };
})();