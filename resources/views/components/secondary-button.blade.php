<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-atlas-navy/20 rounded-md font-semibold text-xs text-atlas-navy uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
