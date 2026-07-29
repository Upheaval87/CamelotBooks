<header class="topnav">
    <div class="flex items-center justify-between h-14 px-5">
        {{-- Left: Mobile hamburger + search --}}
        <div class="flex items-center gap-3">
            <button type="button"
                    class="lg:hidden p-2 rounded-lg text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 transition-colors"
                    x-on:click="$dispatch('open-mobile-sidebar')">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        {{-- Right: actions --}}
        <div class="flex items-center gap-2">
            {{-- Theme toggle --}}
            <button type="button"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    x-on:click="dark = !dark; document.documentElement.classList.toggle('dark')"
                    class="btn-icon btn-ghost rounded-lg"
                    title="Toggle dark mode">
                <svg x-show="!dark" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="dark" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
    </div>
</header>
