<div>
    @if ($producto?->estaDisponible())
        <div @class(['flex gap-2', 'mt-4' => $conCantidad, 'mt-3' => ! $conCantidad])>
            @if ($conCantidad)
                <label class="sr-only" for="cantidad-{{ $productoId }}">Cantidad</label>
                <select id="cantidad-{{ $productoId }}" wire:model="cantidad" class="rounded-lg border-slate-300 text-sm">
                    @for ($i = 1; $i <= min(10, $producto->disponible()); $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            @endif
            <button type="button" wire:click="agregar" wire:loading.attr="disabled"
                    @class([
                        'flex flex-1 items-center justify-center gap-2 rounded-lg font-semibold text-white transition disabled:opacity-60',
                        'bg-acento-600 hover:bg-acento-700' => ! $agregado,
                        'bg-emerald-600 hover:bg-emerald-700' => $agregado,
                        'px-5 py-3' => $conCantidad,
                        'px-3 py-2 text-sm' => ! $conCantidad,
                    ])>
                <x-icono nombre="carrito" class="size-5" />
                <span wire:loading.remove wire:target="agregar">{{ $agregado ? 'Agregado' : 'Agregar al carrito' }}</span>
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
