<x-admin-layout :title="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario interno'">
    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a usuarios</a>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,42rem)_1fr]">
    <form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}"
          class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        @csrf
        @if ($usuario->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="nombre" value="Nombre completo" />
                <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $usuario->nombre)" required maxlength="120" />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $usuario->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="telefono" value="Teléfono" />
                <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $usuario->telefono)" />
                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="rol" value="Rol" />
                <select id="rol" name="rol" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol', $usuario->rol?->value) === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('rol')" class="mt-2" />
            </div>
        </div>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-1 text-sm font-medium text-slate-700">{{ $usuario->exists ? 'Cambiar contraseña (opcional)' : 'Contraseña inicial' }}</legend>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" value="Contraseña" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $usuario->exists" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                </div>
            </div>
        </fieldset>

        <label class="flex items-center gap-2">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500" @checked(old('activo', $usuario->exists ? $usuario->activo : true))>
            <span class="text-sm text-slate-700">Cuenta activa (puede iniciar sesión)</span>
        </label>

        <div class="flex items-center gap-3">
            <x-primary-button>{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</x-primary-button>
            <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-slate-600 hover:underline">Cancelar</a>
        </div>
        @unless ($usuario->exists)
            <p class="text-xs text-slate-500">La cuenta queda verificada y el sistema le pedirá cambiar la contraseña en su primer ingreso.</p>
        @endunless
    </form>

    @if ($usuario->exists)
        <div class="space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Resumen de la cuenta</h2>
                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-slate-500">Registrado</dt><dd>{{ $usuario->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="text-slate-500">Último acceso</dt><dd>{{ $usuario->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                    <dt class="text-slate-500">Correo verificado</dt><dd>{{ $usuario->email_verified_at ? 'Sí' : 'No' }}</dd>
                    <dt class="text-slate-500">NIT</dt><dd>{{ $usuario->nit }}</dd>
                    <dt class="text-slate-500">Contraseña</dt><dd>{{ $usuario->debe_cambiar_password ? 'Temporal (pendiente de cambio)' : 'Definida por el usuario' }}</dd>
                </dl>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Direcciones de entrega</h2>
                @forelse ($direcciones as $direccion)
                    <address class="mt-3 border-t border-slate-100 pt-3 text-sm leading-relaxed not-italic text-slate-600 first-of-type:border-0 first-of-type:pt-0">
                        <span class="font-medium text-slate-800">{{ $direccion->alias }}</span>
                        @if ($direccion->es_principal)<span class="ml-1 rounded-full bg-marca-50 px-2 py-0.5 text-xs font-semibold text-marca-700">Principal</span>@endif
                        <br>{{ $direccion->nombre_receptor }} · Tel. {{ $direccion->telefono }}<br>
                        {{ $direccion->direccion }}, {{ $direccion->municipio }}, {{ $direccion->departamento->nombre }}
                    </address>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Sin direcciones registradas.</p>
                @endforelse
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Actividad reciente</h2>
                    <a href="{{ route('admin.bitacora.index') }}" class="text-xs font-medium text-marca-600 hover:underline">Ver bitácora</a>
                </div>
                <ul class="mt-2 divide-y divide-slate-100 text-sm">
                    @forelse ($actividad as $registro)
                        <li class="flex justify-between gap-3 py-2">
                            <span>
                                {{ Str::of($registro->accion)->replace('_', ' ')->ucfirst() }}
                                <span class="block text-xs text-slate-500">por {{ $registro->usuario?->nombre ?? 'Sistema' }}</span>
                            </span>
                            <time class="shrink-0 text-xs text-slate-400">{{ $registro->created_at->diffForHumans() }}</time>
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">Sin actividad registrada.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif
    </div>
</x-admin-layout>
