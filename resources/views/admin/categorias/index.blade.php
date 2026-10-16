<x-admin-layout title="Categorías">
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-4 py-3">Categoría</th><th class="px-4 py-3">Tipo de componente</th><th class="px-4 py-3 text-right">Productos</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($categorias as $categoria)
                        <tr x-data="{ editar: false }">
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-2 font-medium text-slate-900"><x-icono :nombre="$categoria->icono" class="size-5 text-marca-500" /> {{ $categoria->nombre }}</span>
                                <span class="text-xs text-slate-500">/categoria/{{ $categoria->slug }} · orden {{ $categoria->orden }}</span>
                                <form x-show="editar" x-cloak method="POST" action="{{ route('admin.categorias.update', $categoria) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.categorias.campos', ['categoria' => $categoria])
                                    <div class="sm:col-span-2"><x-primary-button>Guardar</x-primary-button></div>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $categoria->tipo_componente?->etiqueta() ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $categoria->productos_count }}</td>
                            <td class="px-4 py-3">
                                <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $categoria->activo, 'bg-slate-100 text-slate-500' => ! $categoria->activo])>{{ $categoria->activo ? 'Visible' : 'Oculta' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right"><button type="button" @click="editar = ! editar" class="text-xs font-semibold text-marca-600 hover:underline">Editar</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.categorias.store') }}" class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="font-semibold text-slate-900">Nueva categoría</h2>
            @include('admin.categorias.campos', ['categoria' => new \App\Models\Categoria(['activo' => true, 'orden' => $categorias->max('orden') + 1, 'icono' => 'paquete'])])
            <x-primary-button class="w-full">Crear categoría</x-primary-button>
        </form>
    </div>
</x-admin-layout>
