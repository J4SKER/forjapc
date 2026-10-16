<x-admin-layout title="Reseñas">
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (\App\Enums\EstadoResena::cases() as $opcion)
            <a href="{{ route('admin.resenas.index', ['estado' => $opcion->value]) }}" @class([
                'rounded-lg px-3 py-1.5 font-medium',
                'bg-marca-700 text-white' => $estado === $opcion,
                'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $estado !== $opcion,
            ])>{{ $opcion->etiqueta() }}@if ($opcion === \App\Enums\EstadoResena::Pendiente && $pendientes) ({{ $pendientes }})@endif</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($resenas as $resena)
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-amber-600">{{ str_repeat('★', $resena->calificacion) }}{{ str_repeat('☆', 5 - $resena->calificacion) }}</p>
                        <h2 class="font-semibold text-slate-900">{{ $resena->titulo }}</h2>
                        <p class="text-xs text-slate-500">{{ $resena->usuario->nombre }} ({{ $resena->usuario->email }}) sobre
                            <a href="{{ route('tienda.producto', $resena->producto->slug) }}" target="_blank" class="text-marca-600 hover:underline">{{ $resena->producto->nombre }}</a>
                            · {{ $resena->created_at->format('d/m/Y H:i') }} @if ($resena->compra_verificada)· <span class="text-emerald-700">compra verificada</span>@endif</p>
                    </div>
                    @if ($resena->estado === \App\Enums\EstadoResena::Pendiente)
                        <div class="flex gap-2">
                            @foreach ([[\App\Enums\EstadoResena::Aprobada, 'Aprobar', 'bg-emerald-600 hover:bg-emerald-700'], [\App\Enums\EstadoResena::Rechazada, 'Rechazar', 'bg-slate-600 hover:bg-slate-700']] as [$nuevo, $texto, $color])
                                <form method="POST" action="{{ route('admin.resenas.update', $resena) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="estado" value="{{ $nuevo->value }}">
                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white {{ $color }}">{{ $texto }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>
                <p class="mt-2 text-sm text-slate-700">{{ $resena->comentario }}</p>
            </article>
        @empty
            <p class="rounded-xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">No hay reseñas {{ mb_strtolower($estado->etiqueta()) }}s.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $resenas->links() }}</div>
</x-admin-layout>
