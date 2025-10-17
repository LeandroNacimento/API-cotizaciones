<!DOCTYPE html>
<html lang="es-AR" class="h-full" style="color-scheme: light dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title : 'App' }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
    @stack('scripts')
    <script defer src="//unpkg.com/alpinejs"></script>
</body>
</html>
