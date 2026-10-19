<x-guest-layout>
    <div class="flex flex-col gap-1.5">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-marca-600 uppercase"><x-icono nombre="escudo" class="size-4" /> Verificación en dos pasos</span>
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">Revisa tu correo</h1>
        <p class="text-sm text-slate-500">Para proteger el panel, enviamos un código de 6 dígitos a <strong class="text-slate-800">{{ $correo }}</strong>. Vence en {{ config('forjapc.dos_pasos.minutos_validez') }} minutos.</p>
    </div>

    <form method="POST" action="{{ route('dos-pasos.store') }}" class="mt-6 flex flex-col gap-4">
        @csrf
        <div class="flex flex-col gap-1">
            <label for="codigo" class="text-sm font-semibold text-slate-800">Código de verificación</label>
            <input id="codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus placeholder="000000"
                   class="w-full rounded-lg border-0 bg-slate-100 py-3.5 text-center font-display text-3xl font-bold tracking-[0.5em] text-slate-900 shadow-sm placeholder:text-slate-300 focus:bg-white focus:ring-2 focus:ring-marca-600">
            <x-input-error :messages="$errors->get('codigo')" />
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700 select-none">
            <input type="checkbox" name="recordar" value="1" class="size-4 rounded border-slate-300 text-marca-800 focus:ring-0">
            Confiar en este equipo durante {{ config('forjapc.dos_pasos.dias_recordar_equipo') }} días
        </label>
        <p class="-mt-2 text-xs text-slate-500">Márcalo solo en tu computadora personal, nunca en un equipo compartido.</p>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-marca-800 py-3.5 font-display font-bold text-white shadow-md transition hover:bg-marca-600">Verificar y entrar</button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-3 text-sm">
        <form method="POST" action="{{ route('dos-pasos.reenviar') }}">
            @csrf
            <button type="submit" class="font-semibold text-marca-600 hover:underline">Enviar otro código</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-slate-500 hover:text-slate-900">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
