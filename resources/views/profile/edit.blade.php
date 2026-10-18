<x-app-layout title="Perfil y seguridad">
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold text-slate-900">Perfil y seguridad</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div class="space-y-6">
            @if (Auth::user()->debe_cambiar_password)
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <x-icono nombre="candado" class="size-5 shrink-0" />
                    <p>Tu cuenta tiene una <strong>contraseña temporal</strong>. Escríbela en "Contraseña actual" y elige una nueva para poder usar el sistema.</p>
                </div>
            @endif

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
