<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-atlas-amber border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-atlas-amber focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
