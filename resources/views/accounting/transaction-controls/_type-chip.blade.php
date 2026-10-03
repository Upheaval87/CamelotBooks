@php
    $module = strtolower((string) ($module ?? 'journal_entry'));
    if (str_contains($module, 'reversal')) {
        $cls = 'j';
        $label = __('Reversal');
        $icon = 'reverse';
    } elseif (str_contains($module, 'repost')) {
        $cls = 'p';
        $label = __('Repost');
        $icon = 'repost';
    } elseif (str_contains($module, 'transfer')) {
        $cls = 't';
        $label = __('Transfer');
        $icon = 'transfer';
    } elseif (str_contains($module, 'deposit') || str_contains($module, 'receipt')) {
        $cls = 'd';
        $label = __('Deposit');
        $icon = 'deposit';
    } else {
        $cls = 'j';
        $label = __('Journal');
        $icon = 'journal';
    }
@endphp
<span class="ty {{ $cls }}">
    @if ($icon === 'deposit')
        <svg viewBox="0 0 24 24"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
    @elseif ($icon === 'transfer')
        <svg viewBox="0 0 24 24"><path d="M4 8h13m0 0-3-3m3 3-3 3M20 16H7m0 0 3-3m-3 3 3 3"/></svg>
    @elseif ($icon === 'repost')
        <svg viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.3-5.6M20 3v4h-4"/></svg>
    @elseif ($icon === 'reverse')
        <svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3"/></svg>
    @else
        <svg viewBox="0 0 24 24"><path d="M4 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v16l-3.5-2-3.5 2-3.5-2L4 21V5Z"/><path d="M8 7h7M8 11h7"/></svg>
    @endif
    {{ $label }}
</span>
