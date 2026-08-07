@php
$flashes = [
    'success' => session('success'),
    'error' => session('error'),
    'warning' => session('warning'),
    'info' => session('info'),
];
$json = json_encode(array_filter($flashes, fn ($v) => $v !== null), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

@if($json !== '[]')
    <div id="feedback-flashes" data-flashes="{{ e($json) }}" hidden></div>
@endif
