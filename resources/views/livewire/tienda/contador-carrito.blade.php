<a href="{{ route('carrito') }}" class="relative flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-marca-700" aria-label="Carrito de compras, {{ $articulos }} artículo(s)">
    <span class="relative">
        <x-icono nombre="carrito" class="size-6" />
        <span class="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full bg-acento-600 text-[11px] font-bold">{{ $articulos > 99 ? '99+' : $articulos }}</span>
    </span>
    <span class="hidden text-left leading-tight lg:block">
        <span class="block text-[11px] text-marca-100">Mi carrito</span>
        <span class="block text-sm font-semibold">Q{{ number_format($total, 2) }}</span>
    </span>
</a>
