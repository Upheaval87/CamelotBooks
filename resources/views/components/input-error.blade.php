@props(['messages' => [], 'class' => ''])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'input-error-text']) }}>
        @foreach ((array) $messages as $message)
            <p>{{ $message }}</p>
        @endforeach
    </div>
@endif
