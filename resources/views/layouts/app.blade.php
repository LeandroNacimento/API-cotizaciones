<!DOCTYPE html>
<html lang="es-AR" class="h-full" style="color-scheme: light dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title : (isset($header) ? strip_tags($header) : config('app.name', 'App')) }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" x-data="{ open:false }">

    {{-- Topbar (mobile y desktop) --}}
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-950/80 backdrop-blur border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between px-4 py-3 md:px-6">
            <div class="flex items-center gap-3">
                <button class="md:hidden inline-flex items-center justify-center rounded-lg p-2 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                        @click="open = true" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <a href="{{ route('dashboard') }}" class="font-semibold">{{ config('app.name','App') }}</a>
            </div>
            <div class="flex items-center gap-2">
                {{-- espacio para acciones (p.ej. toggle dark) --}}
            </div>
        </div>
    </header>

    {{-- Contenedor principal: sidebar + contenido, sin desplazamientos raros --}}
    <div class="min-h-[calc(100vh-4rem)] md:flex">
        {{-- Sidebar estático en desktop --}}
        <aside class="hidden md:block w-64 shrink-0 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
            <nav class="px-3 py-4">
                @php
                    $linkBase = 'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium';
                    $active   = 'bg-indigo-600 text-white shadow-sm';
                    $idle     = 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800';
                @endphp

                <a href="{{ route('dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
                    <svg class="h-5 w-5 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('cotizar') }}" class="mt-1 {{ $linkBase }} {{ request()->routeIs('cotizar') ? $active : $idle }}">
                    <svg class="h-5 w-5 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    <span>Cotizar</span>
                </a>
            </nav>
        </aside>

        {{-- Sidebar off-canvas en mobile --}}
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm md:hidden" @click="open=false"></div>
        <aside class="fixed inset-y-0 left-0 z-50 w-64 md:hidden transform transition-transform duration-200
                       border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950"
               :class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center justify-between h-14 px-4 border-b border-gray-200 dark:border-gray-800">
                <span class="font-semibold">{{ config('app.name','App') }}</span>
                <button @click="open=false" class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Cerrar menú">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6l-12 12" />
                    </svg>
                </button>
            </div>
            <nav class="px-3 py-4">
                <a href="{{ route('dashboard') }}" @click="open=false"
                   class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
                    <svg class="h-5 w-5 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('cotizar') }}" @click="open=false"
                   class="mt-1 {{ $linkBase }} {{ request()->routeIs('cotizar') ? $active : $idle }}">
                    <svg class="h-5 w-5 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    <span>Cotizar</span>
                </a>
            </nav>
        </aside>

        {{-- Contenido --}}
        <main class="flex-1 p-4 md:p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    <script defer src="//unpkg.com/alpinejs"></script>
    @stack('scripts')
</body>
</html>
