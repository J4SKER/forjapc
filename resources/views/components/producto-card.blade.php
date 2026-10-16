@props(['producto'])

@php
    $especificacion = $producto->especificacion();
    $imagen = $producto->imagenPrincipalUrl();
@endphp

<article class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-marca-500 hover:shadow-md">
    <a href="{{ route('tienda.producto', $producto->slug) }}" class="relative flex aspect-square items-center justify-center bg-slate-50 p-4">
        @if ($imagen)
            <img src="{{ $imagen }}" alt="{{ $producto->nombre }}" loading="lazy" class="h-full w-full object-contain transition group-hover:scale-105">
        @else
            <x-icono :nombre="$producto->categoria->icono" class="size-24 text-slate-300 transition group-hover:scale-105 group-hover:text-marca-500" stroke-width="1" />
        @endif

        <div class="absolute top-2 left-2 flex flex-col gap-1">
            @if ($producto->enOferta())
                <span class="rounded-md bg-acento-600 px-2 py-0.5 text-xs font-bold text-white">-{{ $producto->porcentajeDescuento() }}%</span>
            @endif
            @unless ($producto->estaDisponible())
                <span class="rounded-md bg-slate-800 px-2 py-0.5 text-xs font-semibold text-white">Agotado</span>
            @endunless
        </div>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-semibold tracking-wide text-marca-600 uppercase">{{ $producto->marca->nombre }}</p>
        <h3 class="mt-1 line-clamp-2 text-sm font-semibold text-slate-900">
            <a href="{{ route('tienda.producto', $producto->slug) }}" class="hover:text-marca-700">{{ $producto->nombre }}</a>
        </h3>
        @if ($especificacion)
            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $especificacion->resumen() }}</p>
        @endif

        <div class="mt-auto pt-3">
            @if ($producto->enOferta())
                <p class="text-xs text-slate-400 line-through">Q{{ number_format($producto->precio_anterior, 2) }}</p>
            @endif
            <p class="text-xl font-extrabold text-slate-900">Q{{ number_format($producto->precio, 2) }}</p>
            <p @class([
                'mt-0.5 text-xs font-medium',
                'text-emerald-700' => $producto->estaDisponible() && ! $producto->stockBajo(),
                'text-amber-700' => $producto->estaDisponible() && $producto->stockBajo(),
                'text-slate-500' => ! $producto->estaDisponible(),
            ])>
                @if (! $producto->estaDisponible())
                    Sin existencia
                @elseif ($producto->stockBajo())
                    ¡Últimas {{ $producto->disponible() }} unidades!
                @else
                    Disponible
                @endif
            </p>
        </div>

        <livewire:tienda.boton-carrito :producto-id="$producto->id" :key="'tarjeta-'.$producto->id" />

        {{ $slot }}
    </div>
</article>
