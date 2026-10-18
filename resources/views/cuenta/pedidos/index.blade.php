<x-app-layout title="Mis pedidos">
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold text-slate-900">Mis pedidos</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div class="space-y-3">
            @forelse ($pedidos as $pedido)
                <a href="{{ route('pedidos.show', $pedido) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-marca-400">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $pedido->numero }}</p>
                        <p class="text-xs text-slate-500">{{ $pedido->created_at->translatedFormat('j \d\e F Y, H:i') }} · {{ $pedido->items_count }} {{ $pedido->items_count === 1 ? 'artículo' : 'artículos' }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
                        <span class="text-lg font-bold">Q{{ number_format($pedido->total, 2) }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                    <x-icono nombre="paquete" class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 text-slate-600">Todavía no tienes pedidos.</p>
                    <a href="{{ route('inicio') }}" class="mt-4 inline-block font-semibold text-acento-600 hover:underline">Ir a la tienda</a>
                </div>
            @endforelse
            <div>{{ $pedidos->links() }}</div>
        </div>
    </div>
</x-app-layout>
