<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-slate-900">Inicia sesión</h1>
    <p class="mt-1 text-sm text-slate-500">Consulta tus pedidos y compra más rápido.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-marca-600 hover:underline" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-marca-600 shadow-sm focus:ring-marca-500" name="remember">
            <span class="text-sm text-slate-600">Mantener mi sesión iniciada</span>
        </label>

        <x-primary-button class="w-full">Ingresar</x-primary-button>
    </form>

    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
        ¿Eres nuevo en ForjaPC?
        <a href="{{ route('register') }}" class="font-semibold text-marca-600 hover:underline">Crea tu cuenta</a>
    </p>
</x-guest-layout>
