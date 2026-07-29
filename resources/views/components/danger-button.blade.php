<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-danger btn-md']) }}>
    {{ $slot }}
</button>
