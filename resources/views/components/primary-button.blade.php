<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-acento-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-acento-700 focus:outline-none focus:ring-2 focus:ring-acento-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
