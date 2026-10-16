@php
    $opciones = [
        ['Resumen', 'usuario', 'dashboard', 'dashboard'],
        ['Mis pedidos', 'paquete', 'pedidos.index', 'pedidos.*'],
        ['Mis configuraciones', 'herramienta', 'configuraciones.index', 'configuraciones.*'],
        ['Mis direcciones', 'ubicacion', 'direcciones.index', 'direcciones.*'],
        ['Perfil y seguridad', 'ajustes', 'profile.edit', 'profile.*'],
    ];
@endphp

<nav class="h-fit rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-200" aria-label="Mi cuenta">
    @foreach ($opciones as [$nombre, $icono, $ruta, $patron])
        <a href="{{ route($ruta) }}" @class([
            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm',
            'bg-marca-50 font-semibold text-marca-700' => request()->routeIs($patron),
            'text-slate-700 hover:bg-slate-50' => ! request()->routeIs($patron),
        ])>
            <x-icono :nombre="$icono" class="size-5" /> {{ $nombre }}
        </a>
    @endforeach
    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
        @csrf
        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-acento-700 hover:bg-slate-50">
            <x-icono nombre="salir" class="size-5" /> Cerrar sesión
        </button>
    </form>
</nav>
