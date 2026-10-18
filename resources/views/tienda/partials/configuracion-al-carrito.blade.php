<button type="button" wire:click="agregarAlCarrito" @disabled(! $puedeComprarse)
        class="flex w-full items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 px-4 py-3.5 font-display font-bold text-white shadow-lg shadow-orange-500/30 transition hover:to-acento-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:from-slate-200 disabled:to-slate-200 disabled:text-slate-500 disabled:shadow-none">
    <x-icono nombre="carrito" class="size-5" /> Agregar al carrito
</button>
