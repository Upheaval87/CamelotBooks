@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'variant' => 'default',
    'labelledby' => null,
    'label' => null,
    'focusable' => false,
])

@php
    // Width is now owned by the shared dialog shell (Appendix A): the shared
    // geometry, not per-call Tailwind max-w utilities. The `maxWidth` prop is
    // kept so every existing <x-modal> call site keeps working unchanged.
    $shellWidth = [
        'sm' => 'dlg-card--sm',
        'md' => '',
        'lg' => 'dlg-card--edit',
        'xl' => 'dlg-card--list',
        '2xl' => '',
        '3xl' => 'dlg-card--list',
        '4xl' => 'dlg-card--list',
    ][$maxWidth] ?? '';

    // Accessible name. An explicit labelledby/label wins; otherwise fall back to
    // a humanised version of the modal name so every dialog is named even
    // though no call site passes a title prop.
    $ariaName = $labelledby ? null : ($label ?? \Illuminate\Support\Str::of($name)->replace(['-', '_'], ' ')->ucfirst()->toString());
@endphp

{{-- Shared shell: .dlg-scrim is the tint scrim + centering context, .dlg-card is
     the white 20px-radius card. `--alpine` tells the shell to stay out of the
     visibility animation because Alpine owns show/hide here. --}}
<div
    x-data="{
        show: @js($show),
        wantsFocus: @js((bool) $focusable),
        /* Shared layer-stack handle and focus-return target are kept on the
           element, not on the reactive data: writing them from x-effect would
           re-trigger x-effect itself. */
        layerRef() { return this.$el.__dlgLayer },
        focusables() {
            /* Single quotes inside the selector: a double quote here would end
               the surrounding HTML attribute and break the parse. */
            let selector = 'a[href], button:not([disabled]), input:not([type=hidden]):not([disabled]), textarea:not([disabled]), select:not([disabled]), details, [tabindex]:not([tabindex=\'-1\'])'
            return [...this.$el.querySelectorAll(selector)]
                .filter(el => el.offsetParent !== null || el === document.activeElement)
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
        focusNext() { let el = this.nextFocusable(); if (el) { try { el.focus(); } catch (e) {} } },
        focusPrev() { let el = this.prevFocusable(); if (el) { try { el.focus(); } catch (e) {} } },
        isTopLayer() { return !window.DialogLayer || window.DialogLayer.top() === this.layerRef() },
        /* Push onto the shared layer stack only while VISIBLE. Registering every
           modal unconditionally would leave closed modals on the stack, so
           DialogLayer.top() would resolve to a hidden layer and Escape would
           target the wrong one. An x-effect drives this (not $watch, whose
           callback assignments are not written back to the Alpine data proxy,
           which would leave the layer on the stack forever). */
        syncLayer() {
            if (!window.DialogLayer) return;
            if (this.show && !this.$el.__dlgLayer) {
                this.$el.__dlgLayer = window.DialogLayer.push(() => { this.show = false });
            } else if (!this.show && this.$el.__dlgLayer) {
                window.DialogLayer.drop(this.$el.__dlgLayer);
                this.$el.__dlgLayer = null;
            }
        },
        /* Remember the opener so focus can be handed back on close, and honour
           the legacy `focusable` attribute (focus the first control on open). */
        syncFocus() {
            if (this.show) {
                if (!this.$el.__dlgWasOpen) {
                    this.$el.__dlgWasOpen = true;
                    this.$el.__dlgReturn = document.activeElement;
                    if (this.wantsFocus) {
                        setTimeout(() => {
                            let first = this.firstFocusable();
                            if (first) { try { first.focus(); } catch (e) {} }
                        }, 100);
                    }
                }
            } else if (this.$el.__dlgWasOpen) {
                this.$el.__dlgWasOpen = false;
                let target = this.$el.__dlgReturn;
                this.$el.__dlgReturn = null;
                if (target && target.focus) { try { target.focus(); } catch (e) {} }
            }
        },
    }"
    x-effect="
        if (show) { window.Dialog && Dialog.scrollLock(true) } else { window.Dialog && Dialog.scrollLock(false) }
        syncLayer()
        syncFocus()
    "
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="!window.DialogLayer ? (show = false) : (isTopLayer() && (show = false))"
    x-on:keydown.tab.prevent="focusNext()"
    x-on:keydown.shift.tab.prevent="focusPrev()"
    x-show="show"
    x-cloak
    class="dlg-scrim dlg-scrim--alpine"
    x-on:click.self="show = false"
>
    <div
        class="dlg-card dlg-card--alpine {{ $shellWidth }}"
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        role="dialog"
        aria-modal="true"
        @if ($ariaName) aria-label="{{ $ariaName }}" @elseif ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    >
        {{ $slot }}
    </div>
</div>
