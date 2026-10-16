<x-guest-layout>
    <div class="text-center">
        <span class="inline-flex rounded-full bg-marca-50 p-4 text-marca-600"><x-icono nombre="correo" class="size-8" /></span>
        <h1 class="mt-4 text-2xl font-extrabold text-slate-900">Verifica tu correo</h1>
        <p class="mt-2 text-sm text-slate-600">
            ¡Gracias por registrarte! Te enviamos un enlace a <strong>{{ Auth::user()->email }}</strong>.
            Ábrelo para activar tu cuenta. Si no lo encuentras, revisa la carpeta de spam o pide uno nuevo.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            Te enviamos un nuevo enlace de verificación.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">Reenviar correo de verificación</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm text-slate-500 underline hover:text-slate-800">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
