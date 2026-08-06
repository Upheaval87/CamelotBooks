<x-app-layout>
    @include('superadmin._nav', ['active' => 'companies'])

    <div class="sa-page py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Module Activation') }} — {{ $company->name }}</h1>
                    <p class="sa-page-subtitle">{{ __('Controls which modules this company’s tenant database exposes. Activation is the single source of truth — tenant-side feature settings are read-only.') }}</p>
                </div>
                <a href="{{ route('superadmin.companies.show', $company) }}" class="sa-btn sa-btn--ghost">{{ __('Back to Company') }}</a>
            </div>

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('Module') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Activated') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                                @php
                                    $state = $moduleStates[$module->id] ?? null;
                                    $isActive = (bool) ($state?->is_active ?? false);
                                    $effectiveActive = $module->is_core || $isActive;
                                @endphp
                                <tr>
                                    <td>
                                        <span style="font-weight: 500; color: var(--sa-ink);">{{ $module->name }}</span>
                                        @if($module->is_core)
                                            <span class="sa-pill sa-pill--muted" style="margin-left: 8px;">Core</span>
                                        @endif
                                        @if($module->description)
                                            <span class="sa-table-sub">{{ $module->description }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($module->is_core)
                                            <span class="sa-pill sa-pill--accent">{{ __('Always On') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.companies.modules.toggle', [$company, $module]) }}">
                                                @csrf
                                                <x-toggle-switch :checked="$isActive" aria-label="{{ __('Toggle :module', ['module' => $module->name]) }}" />
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if($module->is_core)
                                            <span style="color: #c8ccd2;">—</span>
                                        @elseif($isActive && $state?->activated_at)
                                            <span style="color: var(--sa-muted); font-size: 12px;">{{ $state->activated_at->format('M j, Y') }}</span>
                                        @else
                                            <span style="color: #c8ccd2;">{{ __('Not activated') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
