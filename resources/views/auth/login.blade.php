<x-guest-layout>
    <div class="mb-6 flex justify-end text-sm text-slate-500">
        <span>¿No tienes cuenta? <a href="{{ route('register') }}" class="font-display font-semibold text-marca-600 hover:text-marca-500">Crea tu cuenta →</a></span>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-marca-600 uppercase"><x-icono nombre="candado" class="size-4" /> Acceso de clientes</span>
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">Inicia sesión</h1>
        <p class="text-sm text-slate-500">Consulta tus pedidos y compra más rápido.</p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 flex flex-col gap-4">
        @csrf

        <x-campo nombre="email" etiqueta="Correo electrónico" icono="correo" tipo="email" placeholder="tu@correo.com" required autofocus autocomplete="username" />

        <x-campo nombre="password" etiqueta="Contraseña" icono="candado" tipo="password" placeholder="••••••••" required autocomplete="current-password">
            @if (Route::has('password.request'))
                <x-slot:accion>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-marca-600 hover:text-marca-500">¿Olvidaste tu contraseña?</a>
                </x-slot:accion>
            @endif
        </x-campo>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2 select-none">
            <input id="remember_me" type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-marca-800 focus:ring-0">
            <span class="text-sm text-slate-700">Mantener mi sesión iniciada en este equipo</span>
        </label>

        <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-lg bg-marca-800 py-3.5 font-display font-bold text-white shadow-md transition hover:bg-marca-600 active:scale-[0.99]">
            Iniciar sesión <x-icono nombre="chevron-derecha" class="size-5 transition group-hover:translate-x-1" />
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-3 rounded-lg bg-slate-100 p-3 text-sm text-slate-600">
        <span class="flex items-center gap-2"><x-icono nombre="chat" class="size-4.5 text-marca-600" /> ¿Problemas para entrar?</span>
        <a href="https://wa.me/{{ \App\Models\ParametroSistema::valor('whatsapp_contacto') }}" target="_blank" rel="noopener" class="font-semibold text-marca-600 hover:underline">Ayuda por WhatsApp</a>
    </div>
</x-guest-layout>
