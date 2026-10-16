<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-slate-900">Confirma tu contraseña</h1>
    <p class="mt-1 text-sm text-slate-500">Esta es un área protegida. Confirma tu contraseña para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Confirmar</x-primary-button>
    </form>
</x-guest-layout>
