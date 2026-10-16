<x-app-layout title="Perfil y seguridad">
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold text-slate-900">Perfil y seguridad</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div class="space-y-6">
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
