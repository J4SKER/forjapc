<x-app-layout :title="$direccion->exists ? 'Editar dirección' : 'Nueva dirección'">
    <x-slot name="header">
        <a href="{{ route('direcciones.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Mis direcciones</a>
        <h1 class="text-2xl font-extrabold text-slate-900">{{ $direccion->exists ? 'Editar dirección' : 'Nueva dirección' }}</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <form method="POST" action="{{ $direccion->exists ? route('direcciones.update', $direccion) : route('direcciones.store') }}"
              class="max-w-2xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            @csrf
            @if ($direccion->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="alias" value="Nombre de la dirección" />
                    <x-text-input id="alias" name="alias" class="mt-1 block w-full" :value="old('alias', $direccion->alias)" placeholder="Casa, Trabajo…" required maxlength="40" />
                    <x-input-error :messages="$errors->get('alias')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nombre_receptor" value="¿Quién recibe?" />
                    <x-text-input id="nombre_receptor" name="nombre_receptor" class="mt-1 block w-full" :value="old('nombre_receptor', $direccion->nombre_receptor)" required maxlength="120" />
                    <x-input-error :messages="$errors->get('nombre_receptor')" class="mt-2" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="telefono" value="Teléfono" />
                    <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $direccion->telefono)" required />
                    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="departamento_id" value="Departamento" />
                    <select id="departamento_id" name="departamento_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                        <option value="">Selecciona…</option>
                        @foreach ($departamentos as $departamento)
                            <option value="{{ $departamento->id }}" @selected((int) old('departamento_id', $direccion->departamento_id) === $departamento->id)>
                                {{ $departamento->nombre }} (envío Q{{ number_format($departamento->tarifa_envio, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('departamento_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="municipio" value="Municipio" />
                    <x-text-input id="municipio" name="municipio" class="mt-1 block w-full" :value="old('municipio', $direccion->municipio)" required maxlength="80" />
                    <x-input-error :messages="$errors->get('municipio')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="direccion" value="Dirección" />
                <x-text-input id="direccion" name="direccion" class="mt-1 block w-full" :value="old('direccion', $direccion->direccion)" placeholder="Calle, avenida, número de casa, zona" required maxlength="255" />
                <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="referencias" value="Referencias (opcional)" />
                <x-text-input id="referencias" name="referencias" class="mt-1 block w-full" :value="old('referencias', $direccion->referencias)" placeholder="Frente al parque, portón negro…" maxlength="255" />
                <x-input-error :messages="$errors->get('referencias')" class="mt-2" />
            </div>

            <label class="flex items-center gap-2">
                <input type="hidden" name="es_principal" value="0">
                <input type="checkbox" name="es_principal" value="1" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500" @checked(old('es_principal', $direccion->es_principal))>
                <span class="text-sm text-slate-700">Usar como dirección principal</span>
            </label>

            <div class="flex items-center gap-3">
                <x-primary-button>Guardar dirección</x-primary-button>
                <a href="{{ route('direcciones.index') }}" class="text-sm text-slate-600 hover:underline">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
