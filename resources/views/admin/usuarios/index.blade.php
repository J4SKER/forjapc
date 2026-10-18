<x-admin-layout title="Usuarios">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="sm:col-span-2">
                <label for="q" class="text-xs font-medium text-slate-600">Buscar</label>
                <x-text-input id="q" name="q" :value="request('q')" placeholder="Nombre o correo" class="mt-1 block w-full" />
            </div>
            <div>
                <label for="rol" class="text-xs font-medium text-slate-600">Rol</label>
                <select id="rol" name="rol" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                    <option value="">Todos</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(request('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado" class="text-xs font-medium text-slate-600">Estado</label>
                <select id="estado" name="estado" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                    <option value="">Cualquier estado</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                    <option value="bloqueados" @selected(request('estado') === 'bloqueados')>Bloqueados</option>
                </select>
            </div>
            <div class="flex items-end"><x-secondary-button type="submit" class="w-full justify-center">Filtrar</x-secondary-button></div>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('admin.usuarios.exportar', request()->only(['q', 'rol', 'estado'])) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <x-icono nombre="lista" class="size-4" /> Exportar CSV
            </a>
            <a href="{{ route('admin.usuarios.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">
                <x-icono nombre="usuario" class="size-4" /> Nuevo usuario interno
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Último acceso</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($usuarios as $usuario)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $usuario->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $usuario->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-slate-100 text-slate-700' => ! $usuario->esPersonal(),
                                'bg-marca-50 text-marca-700' => $usuario->esPersonal(),
                            ])>{{ $usuario->rol->etiqueta() }}</span>
                            @if ($usuario->esPropietario())<span class="ml-1 rounded-full bg-marca-800 px-2 py-0.5 text-xs font-semibold text-cian-400">Propietario</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($usuario->estaBloqueadoTemporalmente())
                                <span class="text-xs font-semibold text-amber-700">Bloqueado hasta {{ $usuario->bloqueado_hasta->format('H:i') }}</span>
                            @elseif (! $usuario->activo)
                                <span class="text-xs font-semibold text-acento-700">Inactivo</span>
                            @elseif (! $usuario->email_verified_at)
                                <span class="text-xs text-slate-500">Correo sin verificar</span>
                            @else
                                <span class="text-xs font-semibold text-emerald-700">Activo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $usuario->ultimo_acceso?->diffForHumans() ?? 'Nunca' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @if ($usuario->estaBloqueadoTemporalmente() && Auth::user()->puedeGestionarA($usuario))
                                    <form method="POST" action="{{ route('admin.usuarios.desbloquear', $usuario) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-amber-700 hover:underline">Desbloquear</button>
                                    </form>
                                @endif
                                @if (Auth::user()->puedeGestionarA($usuario))
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="text-xs font-semibold text-marca-600 hover:underline">Editar</a>
                                @else
                                    <span class="text-xs text-slate-400" title="Solo el propietario puede modificar cuentas de administrador">Solo propietario</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No se encontraron usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $usuarios->links() }}</div>
</x-admin-layout>
