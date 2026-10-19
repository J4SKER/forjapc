<x-app-layout title="Mis configuraciones">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-slate-900">Mis configuraciones</h1>
            <a href="{{ route('tienda.configurador') }}" class="inline-flex items-center gap-2 rounded-lg bg-acento-600 px-4 py-2 text-sm font-semibold text-white hover:bg-acento-700">
                <x-icono nombre="herramienta" class="size-4" /> Armar una nueva
            </a>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div class="space-y-4">
            @forelse ($configuraciones as $item)
                @php($configuracion = $item['modelo'])
                <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-900">{{ $configuracion->nombre }}</h2>
                            <p class="text-xs text-slate-500">Actualizada {{ $configuracion->updated_at->diffForHumans() }} · {{ $configuracion->incluye_armado ? 'con armado' : 'sin armado' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-extrabold">Q{{ number_format($item['total'], 2) }}</p>
                            <p class="text-xs {{ $item['valida'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $item['valida'] ? 'Lista para comprar' : 'Incompleta o con piezas sin existencia' }}</p>
                        </div>
                    </div>
                    <ul class="mt-3 grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                        @foreach ($item['seleccion']->lineas() as $linea)
                            <li><span class="text-xs text-slate-400">{{ $linea['tipo']->etiqueta() }}:</span> {{ $linea['cantidad'] > 1 ? $linea['cantidad'].'× ' : '' }}{{ $linea['producto']->nombre }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        <a href="{{ route('tienda.configurador.ver', $configuracion) }}" class="font-semibold text-marca-600 hover:underline">Abrir en el configurador</a>
                        <button type="button" x-data="copiar" data-enlace="{{ route('tienda.configurador.ver', $configuracion) }}" @click="copiar($el.dataset.enlace)" x-text="copiado ? 'Enlace copiado' : 'Copiar enlace para compartir'" class="font-semibold text-slate-600 hover:underline">Copiar enlace para compartir</button>
                        <form method="POST" action="{{ route('configuraciones.destroy', $configuracion) }}" data-confirmar="¿Eliminar esta configuración?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-acento-600 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                    <x-icono nombre="herramienta" class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 text-slate-600">Todavía no guardaste ninguna configuración.</p>
                    <a href="{{ route('tienda.configurador') }}" class="mt-4 inline-block font-semibold text-acento-600 hover:underline">Armar mi primera PC</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
