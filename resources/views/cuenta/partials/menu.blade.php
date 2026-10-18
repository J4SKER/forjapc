@php
    $cliente = Auth::user();
    $iniciales = collect(explode(' ', $cliente->nombre))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $opciones = [
        ['Resumen', 'panel', 'dashboard', 'dashboard', null],
        ['Mis pedidos', 'paquete', 'pedidos.index', 'pedidos.*', $cliente->pedidos()->count()],
        ['Mis configuraciones', 'cpu', 'configuraciones.index', 'configuraciones.*', $cliente->configuraciones()->count()],
        ['Mis direcciones', 'ubicacion', 'direcciones.index', 'direcciones.*', $cliente->direcciones()->count()],
        ['Perfil y seguridad', 'ajustes', 'profile.edit', 'profile.*', null],
    ];
@endphp

<aside class="flex h-fit flex-col gap-4" aria-label="Mi cuenta">
    <div class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
        <div class="flex items-center gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-marca-800 font-display text-lg font-bold text-cian-400 shadow-sm">{{ $iniciales }}</span>
            <span class="flex min-w-0 flex-col">
                <span class="truncate font-display font-bold text-slate-900">{{ $cliente->nombre }}</span>
                <span class="truncate text-xs text-slate-500">{{ $cliente->email }}</span>
            </span>
        </div>
        <div class="flex items-center justify-between rounded-lg bg-slate-100 px-3 py-1.5 text-xs">
            <span class="flex items-center gap-1 font-bold text-marca-600 uppercase"><x-icono nombre="escudo" class="size-4" /> {{ $cliente->hasVerifiedEmail() ? 'Correo verificado' : 'Correo sin verificar' }}</span>
            <span class="font-semibold text-slate-600">NIT {{ $cliente->nit }}</span>
        </div>
    </div>

    <nav class="flex flex-col gap-1 rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-200/70">
        @foreach ($opciones as [$nombre, $icono, $ruta, $patron, $total])
            @php
                $activa = request()->routeIs($patron);
            @endphp
            <a href="{{ route($ruta) }}" @if ($activa) aria-current="page" @endif @class([
                'flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm transition',
                'bg-marca-800 font-semibold text-white' => $activa,
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $activa,
            ])>
                <span class="flex items-center gap-3"><x-icono :nombre="$icono" @class(['size-5', 'text-cian-400' => $activa]) /> {{ $nombre }}</span>
                @if ($total !== null)
                    <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-white/15 text-white' => $activa, 'bg-slate-200 text-slate-800' => ! $activa])>{{ $total }}</span>
                @elseif ($activa)
                    <span class="size-2 rounded-full bg-cian-400"></span>
                @endif
            </a>
        @endforeach
        <div class="mx-3 my-1 h-px bg-slate-100"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                <x-icono nombre="salir" class="size-5" /> Cerrar sesión
            </button>
        </form>
    </nav>

    <div class="hidden flex-col gap-1 rounded-xl bg-slate-200/60 p-4 lg:flex">
        <span class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-marca-600 uppercase"><x-icono nombre="chat" class="size-4" /> ¿Necesitas ayuda?</span>
        <p class="text-xs text-slate-600">Con tus pedidos, garantías o para armar tu PC.</p>
        <span class="mt-1 text-sm font-bold text-slate-900">{{ \App\Models\ParametroSistema::valor('telefono_contacto') }}</span>
        <a href="https://wa.me/{{ \App\Models\ParametroSistema::valor('whatsapp_contacto') }}" target="_blank" rel="noopener" class="text-xs font-semibold text-marca-600 hover:underline">Escribir por WhatsApp</a>
    </div>
</aside>
