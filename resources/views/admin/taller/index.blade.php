<x-admin-layout title="Taller de armado">
    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        <a href="{{ route('admin.taller.index') }}" @class(['rounded-full px-3 py-1.5 font-semibold', 'bg-marca-700 text-white' => ! $estado, 'bg-white text-slate-600 ring-1 ring-slate-200' => $estado])>En curso</a>
        @foreach ($estados as $opcion)
            <a href="{{ route('admin.taller.index', ['estado' => $opcion->value]) }}" @class(['rounded-full px-3 py-1.5 font-semibold', 'bg-marca-700 text-white' => $estado === $opcion, 'bg-white text-slate-600 ring-1 ring-slate-200' => $estado !== $opcion])>{{ $opcion->etiqueta() }}</a>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($ordenes as $orden)
            <a href="{{ route('admin.taller.show', $orden) }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-marca-400">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $orden->nombre_configuracion }}</p>
                        <p class="text-xs text-slate-500">{{ $orden->pedido->numero }} · {{ $orden->pedido->usuario->nombre }}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $orden->estado->color() }}">{{ $orden->estado->etiqueta() }}</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Técnico: {{ $orden->tecnico?->nombre ?? 'sin asignar' }} · Creada {{ $orden->created_at->diffForHumans() }}</p>
                @php($hechos = collect($orden->checklist ?? [])->filter()->count())
                <div class="mt-2 h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full bg-marca-500" data-ancho="{{ round($hechos / count(\App\Models\OrdenArmado::CHECKLIST) * 100) }}%"></div></div>
            </a>
        @empty
            <p class="rounded-xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200 md:col-span-2 xl:col-span-3">No hay órdenes de armado {{ $estado ? 'en este estado' : 'en curso' }}.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $ordenes->links() }}</div>
</x-admin-layout>
