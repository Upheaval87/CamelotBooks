{{-- Top Navigation Bar --}}
@php
    $currentCompany = session('current_company_id') ? \App\Models\Company::find(session('current_company_id')) : null;
    $userCompanies = Auth::user()->companies ?? collect();
@endphp

<header class="atlas-topnav sticky top-0 z-30 h-14 flex items-center px-6 gap-4 max-lg:pl-16">

    {{-- Mobile hamburger --}}
    <button @click="$dispatch('open-mobile-sidebar')" class="lg:hidden p-1.5 -ml-2 rounded-md hover:bg-navy/5 transition-colors">
        <svg class="w-5 h-5 text-atlas-navy" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Search (center-left) --}}
    <div class="flex-1 max-w-md hidden sm:block" x-data="{ show: false }" @keydown.escape.window="show = false" @click.away="show = false">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search…" class="w-full pl-9 pr-12 py-1.5 text-sm bg-atlas-canvas border border-gray-200 rounded-lg focus:border-atlas-blue focus:ring-1 focus:ring-atlas-blue focus:shadow-[0_0_0_3px_rgba(101,145,224,0.2)] transition-all outline-none">
            <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-medium text-navy-400 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">⌘K</kbd>
        </div>
    </div>

    <div class="flex-1 sm:hidden"></div>

    {{-- Right side controls --}}
    <div class="flex items-center gap-3">

        {{-- Theme toggle (icon only) --}}
        <button class="atlas-theme-toggle" title="Toggle theme">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>

        {{-- Company / Branch chip --}}
        @if($currentCompany)
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-atlas-navy bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
                <span class="max-w-[120px] truncate">{{ $currentCompany->name }}</span>
                <svg class="w-3.5 h-3.5 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                @foreach($userCompanies as $uc)
                <a href="{{ route('companies.select', $uc->id) }}" class="block px-4 py-2 text-sm {{ $uc->id === session('current_company_id') ? 'bg-atlas-blue/10 text-atlas-navy font-medium' : 'text-atlas-navy hover:bg-gray-50' }}">
                    {{ $uc->name }}
                    <span class="text-[10px] text-navy-400 ml-1">{{ $uc->pivot->role }}</span>
                </a>
                @endforeach
                <div class="border-t border-gray-100 my-1"></div>
                <a href="{{ route('companies.index') }}" class="block px-4 py-2 text-sm text-navy-500 hover:bg-gray-50">All Companies</a>
            </div>
        </div>
        @endif

        {{-- Notification bell --}}
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="relative p-1.5 rounded-md hover:bg-navy/5 transition-colors">
                <svg class="w-5 h-5 text-atlas-navy" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-atlas-amber rounded-full"></span>
            </button>
        </div>

        {{-- User pill (avatar + name + chevron) --}}
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="inline-flex items-center gap-2 pl-1 pr-3 py-1 text-sm font-medium text-atlas-navy bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
                <div class="w-7 h-7 rounded-full bg-atlas-amber/20 flex items-center justify-center">
                    <span class="text-atlas-amber text-xs font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <span class="max-w-[80px] truncate">{{ explode(' ', Auth::user()->name)[0] }}</span>
                <svg class="w-3.5 h-3.5 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                <div class="px-4 py-2 border-b border-gray-100">
                    <div class="text-sm font-medium text-atlas-navy">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-navy-400">{{ Auth::user()->email }}</div>
                </div>
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-atlas-navy hover:bg-gray-50">Profile</a>
                @if(Auth::user()->hasAnyRoleInCompany(['system_admin', 'company_admin']))
                <a href="{{ route('system-settings.index', 'company') }}" class="block px-4 py-2 text-sm text-atlas-navy hover:bg-gray-50">Settings</a>
                @endif
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-atlas-amber hover:bg-gray-50">Sign Out</button>
                </form>
            </div>
        </div>
    </div>
</header>
