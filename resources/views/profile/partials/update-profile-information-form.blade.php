<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Datos personales</h2>
        <p class="mt-1 text-sm text-slate-600">Estos datos se usan para tus pedidos y tu facturación.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="nombre" value="Nombre completo" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $user->nombre)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div x-data="{ correo: @js(old('email', $user->email)), original: @js($user->email) }">
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" x-model="correo" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            <div x-show="correo.trim().toLowerCase() !== original" x-cloak class="mt-3 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                <x-input-label for="password_actual" value="Para cambiar tu correo, escribe tu contraseña actual" />
                <x-text-input id="password_actual" name="password_actual" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <p class="mt-1 text-xs text-amber-900">Avisaremos a tu correo anterior y tendrás que verificar el nuevo.</p>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('password_actual')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-800">
                    Tu correo no está verificado.
                    <button form="send-verification" class="text-sm text-marca-600 underline hover:text-marca-800">Reenviar el correo de verificación.</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-emerald-700">Te enviamos un nuevo enlace de verificación.</p>
                @endif
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="telefono" value="Teléfono" />
                <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $user->telefono)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
            </div>
            <div>
                <x-input-label for="nit" value="NIT (o CF)" />
                <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full uppercase" :value="old('nit', $user->nit)" maxlength="15" required />
                <x-input-error class="mt-2" :messages="$errors->get('nit')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="avisoTemporal" x-show="show" x-transition class="text-sm text-emerald-700">Guardado.</p>
            @endif
        </div>
    </form>
</section>
