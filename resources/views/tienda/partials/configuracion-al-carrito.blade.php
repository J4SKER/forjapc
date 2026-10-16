<button type="button" wire:click="agregarAlCarrito" @disabled(! $puedeComprarse)
        class="w-full rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700 disabled:cursor-not-allowed disabled:opacity-50">
    {{ $puedeComprarse ? 'Agregar al carrito' : 'Completa una configuración compatible' }}
</button>
