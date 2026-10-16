<x-admin-layout title="Marcas">
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-4 py-3">Marca</th><th class="px-4 py-3 text-right">Productos</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($marcas as $marca)
                        <tr x-data="{ editar: false }">
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-900">{{ $marca->nombre }}</span>
                                <form x-show="editar" x-cloak method="POST" action="{{ route('admin.marcas.update', $marca) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <x-text-input name="nombre" :value="$marca->nombre" aria-label="Nombre" class="text-sm" required />
                                    <label class="flex items-center gap-1 text-xs"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked($marca->activo) class="rounded border-slate-300"> Activa</label>
                                    <x-primary-button>Guardar</x-primary-button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $marca->productos_count }}</td>
                            <td class="px-4 py-3 text-xs font-semibold {{ $marca->activo ? 'text-emerald-700' : 'text-slate-400' }}">{{ $marca->activo ? 'Activa' : 'Inactiva' }}</td>
                            <td class="px-4 py-3 text-right"><button type="button" @click="editar = ! editar" class="text-xs font-semibold text-marca-600 hover:underline">Editar</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.marcas.store') }}" class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="font-semibold text-slate-900">Nueva marca</h2>
            <div>
                <x-input-label for="nombre-marca" value="Nombre" />
                <x-text-input id="nombre-marca" name="nombre" class="mt-1 block w-full" required maxlength="60" />
                <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
            </div>
            <x-primary-button class="w-full">Crear marca</x-primary-button>
        </form>
    </div>
</x-admin-layout>
