@props(['head' => null, 'minWidth' => '960px'])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-[12px] border border-shell bg-row']) }}>
    <table class="w-full border-collapse text-sm" style="min-width: {{ $minWidth }}">
        @if($head)
            <thead>
                <tr>
                    {{ $head }}
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
    </table>
</div>
