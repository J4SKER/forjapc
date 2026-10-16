<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'ForjaPC') }}</title>
        <meta name="description" content="ForjaPC: componentes de cómputo, monitores, periféricos y computadoras armadas a la medida con envío a toda Guatemala.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="flex min-h-screen flex-col bg-slate-100 font-sans text-slate-800 antialiased">
        @include('partials.tienda.encabezado')

        <main class="flex-1">
            @isset($header)
                <div class="border-b border-slate-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            @if (session('estado'))
                <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('estado') }}
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>

        @include('partials.tienda.pie')

        @livewireScripts
    </body>
</html>
