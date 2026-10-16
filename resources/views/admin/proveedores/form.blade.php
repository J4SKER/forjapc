<x-admin-layout :title="$proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor'">
    <a href="{{ route('admin.proveedores.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a proveedores</a>

    <form method="POST" action="{{ $proveedor->exists ? route('admin.proveedores.update', $proveedor) : route('admin.proveedores.store') }}"
          class="mt-4 max-w-2xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @if ($proveedor->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <x-input-label for="nombre" value="Nombre o razón social" />
                <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $proveedor->nombre)" required />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="nit" value="NIT" />
                <x-text-input id="nit" name="nit" class="mt-1 block w-full uppercase" :value="old('nit', $proveedor->nit)" required />
                <x-input-error :messages="$errors->get('nit')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="contacto" value="Persona de contacto" />
                <x-text-input id="contacto" name="contacto" class="mt-1 block w-full" :value="old('contacto', $proveedor->contacto)" />
            </div>
            <div>
                <x-input-label for="telefono" value="Teléfono" />
                <x-text-input id="telefono" name="telefono" class="mt-1 block w-full" :value="old('telefono', $proveedor->telefono)" />
            </div>
            <div>
                <x-input-label for="email" value="Correo" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $proveedor->email)" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="sm:col-span-3">
                <x-input-label for="direccion" value="Dirección" />
                <x-text-input id="direccion" name="direccion" class="mt-1 block w-full" :value="old('direccion', $proveedor->direccion)" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" @checked(old('activo', $proveedor->activo)) class="rounded border-slate-300 text-marca-600"> Activo
        </label>

        <x-primary-button>{{ $proveedor->exists ? 'Guardar cambios' : 'Crear proveedor' }}</x-primary-button>
    </form>
</x-admin-layout>
