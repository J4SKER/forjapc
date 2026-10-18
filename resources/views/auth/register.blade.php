<x-guest-layout>
    <div class="mb-6 flex justify-end text-sm text-slate-500">
        <span>¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-display font-semibold text-marca-600 hover:text-marca-500">Inicia sesión →</a></span>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-marca-600 uppercase"><x-icono nombre="usuario" class="size-4" /> Cuenta nueva</span>
        <h1 class="font-display text-3xl font-bold tracking-tight text-slate-900">Crea tu cuenta</h1>
        <p class="text-sm text-slate-500">Guarda tus configuraciones y da seguimiento a tus pedidos.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 flex flex-col gap-4" x-data="{ nit: @js(old('nit', 'CF')) }">
        @csrf

        <x-campo nombre="nombre" etiqueta="Nombre completo" icono="usuario" placeholder="Como aparece en tu DPI" required autofocus autocomplete="name" />
        <x-campo nombre="email" etiqueta="Correo electrónico" icono="correo" tipo="email" placeholder="tu@correo.com" required autocomplete="username" ayuda="Te enviaremos un enlace para verificarlo." />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nombre="telefono" etiqueta="Teléfono (opcional)" icono="telefono" tipo="tel" placeholder="5555 5555" autocomplete="tel" />
            <div class="flex flex-col gap-1">
                <x-campo nombre="nit" etiqueta="NIT" icono="lista" x-model="nit" maxlength="15" class="uppercase placeholder:normal-case" placeholder="1234567-8" />
                <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600 select-none">
                    <input type="checkbox" :checked="nit.trim().toUpperCase() === 'CF'" @change="nit = $event.target.checked ? 'CF' : ''" class="size-4 rounded border-slate-300 text-marca-600 focus:ring-0"> Consumidor final (CF)
                </label>
            </div>
        </div>

        <x-campo nombre="password" etiqueta="Contraseña" icono="candado" tipo="password" required autocomplete="new-password" ayuda="Mínimo 8 caracteres." />
        <x-campo nombre="password_confirmation" etiqueta="Confirma tu contraseña" icono="candado" tipo="password" required autocomplete="new-password" />

        <button type="submit" class="group mt-1 flex w-full items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 py-3.5 font-display font-bold text-white shadow-lg shadow-orange-500/30 transition hover:to-acento-700 active:scale-[0.99]">
            Crear cuenta <x-icono nombre="chevron-derecha" class="size-5 transition group-hover:translate-x-1" />
        </button>
    </form>
</x-guest-layout>
