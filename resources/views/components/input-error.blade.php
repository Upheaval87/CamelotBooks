@props(['messages' => [], 'class' => ''])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'text-sm text-atlas-danger mt-1']) }}>
        @foreach ((array) $messages as $message)
            <p>{{ $message }}</p>
        @endforeach
    </div>
@endif
