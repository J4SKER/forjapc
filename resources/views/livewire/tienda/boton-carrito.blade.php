<div>
    @if ($producto?->estaDisponible())
        <div @class(['flex gap-2', 'flex-col sm:flex-row' => $conCantidad, 'mt-3' => ! $conCantidad])>
            @if ($conCantidad)
                <div class="flex h-12 items-center justify-between rounded-lg bg-slate-200/70 p-1 shadow-inner sm:w-36" x-data="{ maximo: {{ min(10, $producto->disponible()) }} }" role="group" aria-label="Cantidad">
                    <button type="button" @click="$wire.cantidad = Math.max(1, $wire.cantidad - 1)" :disabled="$wire.cantidad <= 1" class="flex size-10 disabled:cursor-not-allowed disabled:opacity-40 items-center justify-center rounded bg-white text-lg font-bold text-slate-900 transition hover:bg-slate-100 active:scale-90" aria-label="Disminuir cantidad">−</button>
                    <span x-text="$wire.cantidad" class="w-12 text-center font-bold text-slate-900" aria-live="polite">{{ $cantidad }}</span>
                    <button type="button" @click="$wire.cantidad = Math.min(maximo, $wire.cantidad + 1)" :disabled="$wire.cantidad >= maximo" :title="$wire.cantidad >= maximo ? 'No hay más unidades disponibles' : null" class="flex size-10 disabled:cursor-not-allowed disabled:opacity-40 items-center justify-center rounded bg-white text-lg font-bold text-slate-900 transition hover:bg-slate-100 active:scale-90" aria-label="Aumentar cantidad">+</button>
                </div>
            @endif
            <button type="button" wire:click="agregar" wire:loading.attr="disabled"
                    @class([
                        'flex flex-1 items-center justify-center gap-2 rounded-lg font-semibold text-white transition active:scale-[0.98] disabled:opacity-60',
                        'bg-emerald-600 hover:bg-emerald-700' => $agregado,
                        'bg-linear-to-r from-acento-500 to-acento-600 font-display text-lg shadow-lg shadow-orange-500/30 hover:-translate-y-0.5 hover:to-acento-700' => $conCantidad && ! $agregado,
                        'bg-black hover:bg-marca-800' => ! $conCantidad && ! $agregado,
                        'h-12 px-5' => $conCantidad,
                        'px-3 py-2 text-sm' => ! $conCantidad,
                    ])>
                <x-icono nombre="carrito" class="size-5" />
                <span wire:loading.remove wire:target="agregar">{{ $agregado ? 'Agregado' : ($conCantidad ? 'Agregar al carrito' : 'Añadir') }}</span>
                <span wire:loading wire:target="agregar">Agregando…</span>
            </button>
        </div>
        @if ($agregado && $conCantidad)
            <a href="{{ route('carrito') }}" class="mt-2 block text-center text-sm font-semibold text-marca-600 hover:underline">Ver carrito y pagar &rarr;</a>
        @endif
    @else
        <p class="mt-3 rounded-lg bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-500">Sin existencia</p>
    @endif

    @if ($mensaje)
        <p class="mt-2 text-xs text-red-600">{{ $mensaje }}</p>
    @endif
</div>
