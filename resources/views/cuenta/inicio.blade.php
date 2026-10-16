<x-app-layout title="Mi cuenta">
    <x-slot name="header">
        <p class="text-sm text-slate-500">Mi cuenta</p>
        <h1 class="text-2xl font-extrabold text-slate-900">¡Hola, {{ Str::before($user->nombre, ' ') }}!</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Pedidos en curso</p>
                    <p class="mt-1 text-3xl font-extrabold text-slate-900">0</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Configuraciones guardadas</p>
                    <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $user->configuraciones()->count() }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm text-slate-500">Cliente desde</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $user->created_at->translatedFormat('F Y') }}</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">Mis datos</h2>
                        <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-marca-600 hover:underline">Editar</a>
                    </div>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Nombre</dt><dd class="text-right font-medium">{{ $user->nombre }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Correo</dt><dd class="truncate text-right font-medium">{{ $user->email }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Teléfono</dt><dd class="text-right font-medium">{{ $user->telefono ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">NIT</dt><dd class="text-right font-medium">{{ $user->nit }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">Dirección de entrega</h2>
                        <a href="{{ route('direcciones.index') }}" class="text-sm font-medium text-marca-600 hover:underline">Administrar</a>
                    </div>
                    @if ($direccionPrincipal)
                        <address class="mt-4 text-sm leading-relaxed not-italic text-slate-700">
                            <span class="font-semibold">{{ $direccionPrincipal->alias }}</span> · {{ $direccionPrincipal->nombre_receptor }}<br>
                            {{ $direccionPrincipal->direccion }}<br>
                            {{ $direccionPrincipal->municipio }}, {{ $direccionPrincipal->departamento->nombre }}<br>
                            Tel. {{ $direccionPrincipal->telefono }}
                        </address>
                    @else
                        <p class="mt-4 text-sm text-slate-500">Aún no tienes direcciones guardadas.</p>
                        <a href="{{ route('direcciones.create') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-acento-600 hover:underline">
                            <x-icono nombre="ubicacion" class="size-4" /> Agregar una dirección
                        </a>
                    @endif
                </section>
            </div>

            <section class="flex flex-col items-start justify-between gap-4 rounded-xl bg-linear-to-r from-marca-800 to-marca-600 p-6 text-white sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold">¿Listo para tu próxima PC?</h2>
                    <p class="text-sm text-white/80">Arma tu configuración y nosotros validamos que todo sea compatible.</p>
                </div>
                <a href="{{ route('tienda.configurador') }}" class="shrink-0 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-marca-800 hover:bg-slate-100">Arma tu PC</a>
            </section>
        </div>
    </div>
</x-app-layout>
