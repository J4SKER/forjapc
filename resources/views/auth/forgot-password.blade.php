<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-slate-900">Recupera tu contraseña</h1>
    <p class="mt-1 text-sm text-slate-500">Escribe tu correo y te enviaremos un enlace de un solo uso para crear una nueva contraseña.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Enviar enlace</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="font-semibold text-marca-600 hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
