<x-guest-layout>
    <div class="flex flex-col gap-1.5">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-marca-600 uppercase"><x-icono nombre="candado" class="size-4" /> Área protegida</span>
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">Confirma tu contraseña</h1>
        <p class="text-sm text-slate-500">Esta acción es sensible. Para protegerte si dejaste la sesión abierta en otro equipo, confirma tu contraseña. No te la volveremos a pedir durante {{ intdiv((int) config('auth.password_timeout'), 60) }} minutos.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 flex flex-col gap-4">
        @csrf
        <x-campo nombre="password" etiqueta="Contraseña" icono="candado" tipo="password" required autofocus autocomplete="current-password" />
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-marca-800 py-3.5 font-display font-bold text-white shadow-md transition hover:bg-marca-600">Confirmar y continuar</button>
    </form>
</x-guest-layout>
