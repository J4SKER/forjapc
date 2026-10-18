@props(['producto'])

@php
    $especificacion = $producto->especificacion();
    $imagen = $producto->imagenPrincipalUrl();
    $disponible = $producto->estaDisponible();
@endphp

<article @class([
    'group relative flex flex-col rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md',
    'opacity-85' => ! $disponible,
])>
    <a href="{{ route('tienda.producto', $producto->slug) }}" class="relative flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-slate-100/80 p-3">
        @if ($imagen)
            <img src="{{ $imagen }}" alt="{{ $producto->nombre }}" loading="lazy" @class(['h-full w-full object-contain transition duration-300 group-hover:scale-105', 'grayscale' => ! $disponible])>
        @else
            <x-icono :nombre="$producto->categoria->icono" class="size-20 text-slate-300 transition duration-300 group-hover:scale-105 group-hover:text-marca-500" stroke-width="1" />
        @endif

        @if ($producto->enOferta())
            <span class="absolute top-1.5 left-1.5 rounded bg-acento-500 px-1.5 py-0.5 text-[10px] font-bold text-white">-{{ $producto->porcentajeDescuento() }}%</span>
        @endif
        @unless ($disponible)
            <span class="absolute top-1.5 left-1.5 rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Agotado</span>
        @endunless
    </a>

    <div class="flex flex-1 flex-col pt-3">
        <div class="flex items-center justify-between gap-2">
            <p class="truncate text-[10px] font-semibold tracking-wide text-slate-500 uppercase">{{ $producto->marca->nombre }}</p>
            @if (! $disponible)
                <span class="shrink-0 rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">Sin inventario</span>
            @elseif ($producto->stockBajo())
                <span class="shrink-0 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">{{ $producto->disponible() === 1 ? 'Última unidad' : 'Últimas '.$producto->disponible() }}</span>
            @else
                <span class="inline-flex shrink-0 items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span> En existencia</span>
            @endif
        </div>
        <h3 class="mt-1 line-clamp-2 font-display text-sm leading-snug font-bold text-slate-900">
            <a href="{{ route('tienda.producto', $producto->slug) }}" class="group-hover:text-marca-600">{{ $producto->nombre }}</a>
        </h3>
        @if ($especificacion)
            <p class="mt-0.5 line-clamp-1 text-[11px] text-slate-500">{{ $especificacion->resumen() }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-baseline gap-x-2 pt-2">
            <span @class(['font-display text-lg font-bold sm:text-xl', 'text-slate-900' => $disponible, 'text-slate-500' => ! $disponible])>Q{{ number_format($producto->precio, 2) }}</span>
            @if ($producto->enOferta())
                <span class="text-[11px] text-slate-500 line-through">Q{{ number_format($producto->precio_anterior, 2) }}</span>
            @endif
        </div>

        <livewire:tienda.boton-carrito :producto-id="$producto->id" :key="'tarjeta-'.$producto->id" />

        {{ $slot }}
    </div>
</article>
