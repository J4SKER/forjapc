<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-slate-900">Crea tu cuenta</h1>
    <p class="mt-1 text-sm text-slate-500">Guarda tus configuraciones y da seguimiento a tus pedidos.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="nombre" value="Nombre completo" />
            <x-text-input id="nombre" class="mt-1 block w-full" type="text" name="nombre" :value="old('nombre')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="telefono" value="Teléfono (opcional)" />
                <x-text-input id="telefono" class="mt-1 block w-full" type="tel" name="telefono" :value="old('telefono')" autocomplete="tel" placeholder="5555 5555" />
                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="nit" value="NIT (o CF)" />
                <x-text-input id="nit" class="mt-1 block w-full uppercase" type="text" name="nit" :value="old('nit', 'CF')" maxlength="15" />
                <x-input-error :messages="$errors->get('nit')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <p class="mt-1 text-xs text-slate-500">Mínimo 8 caracteres.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirma tu contraseña" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Crear cuenta</x-primary-button>
    </form>

    <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="font-semibold text-marca-600 hover:underline">Inicia sesión</a>
    </p>
</x-guest-layout>
