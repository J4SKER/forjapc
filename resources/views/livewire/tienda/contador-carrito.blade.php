<a href="{{ route('carrito') }}" class="flex min-h-11 items-center gap-2 rounded-lg bg-cian-900/80 px-2.5 py-1 shadow-[0_0_12px_rgba(76,215,246,0.15)] hover:bg-cian-900" aria-label="Carrito de compras, {{ $articulos }} artículo(s)">
    <span class="relative flex size-6 items-center justify-center">
        <x-icono nombre="carrito" class="size-5 text-cian-400" />
        <span class="absolute -top-1.5 -right-2 flex size-4 items-center justify-center rounded-full bg-marca-500 text-[10px] font-bold">{{ $articulos > 99 ? '99+' : $articulos }}</span>
    </span>
    <span class="flex flex-col text-right leading-tight">
        <span class="text-[10px] font-bold tracking-wide text-marca-300 uppercase">Carrito</span>
        <span class="font-display text-xs font-bold text-cian-400 sm:text-sm">Q{{ number_format($total, 2) }}</span>
    </span>
</a>
