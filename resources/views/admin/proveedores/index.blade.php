<x-admin-layout title="Proveedores">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" class="flex gap-2">
            <x-text-input name="q" :value="request('q')" placeholder="Nombre o NIT" aria-label="Buscar" />
            <x-secondary-button type="submit">Buscar</x-secondary-button>
        </form>
        <a href="{{ route('admin.proveedores.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">Nuevo proveedor</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Proveedor</th><th class="px-4 py-3">NIT</th><th class="px-4 py-3">Contacto</th><th class="px-4 py-3 text-right">Compras</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($proveedores as $proveedor)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $proveedor->nombre }} @unless ($proveedor->activo)<span class="ml-1 text-xs text-slate-400">(inactivo)</span>@endunless</td>
                        <td class="px-4 py-3">{{ $proveedor->nit }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $proveedor->contacto }}<br><span class="text-xs">{{ $proveedor->telefono }} {{ $proveedor->email }}</span></td>
                        <td class="px-4 py-3 text-right">{{ $proveedor->compras_count }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.proveedores.edit', $proveedor) }}" class="text-xs font-semibold text-marca-600 hover:underline">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Sin proveedores.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $proveedores->links() }}</div>
</x-admin-layout>
