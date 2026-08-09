<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Evita el parpadeo de tema (FOUC) antes de que Alpine tome el control -->
        <script>
            if (localStorage.getItem('dark-mode') === 'true' ||
                (localStorage.getItem('dark-mode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="font-sans antialiased"
        x-data="{ darkMode: document.documentElement.classList.contains('dark'), sidebarOpen: false }"
        x-init="$watch('darkMode', value => {
            localStorage.setItem('dark-mode', value);
            document.documentElement.classList.toggle('dark', value);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: value } }));
        })"
    >
        <div>
            <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
                <x-sidebar />

                <div class="flex min-w-0 flex-1 flex-col">
                    <x-topbar />

                    <main class="flex-1 overflow-y-auto">
                        @if (isset($header))
                            <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endif

                        {{ $slot }}
                    </main>
                </div>
            </div>

            <x-toast-container />
            <livewire:transaction-form />
        </div>

        @livewireScripts
    </body>
</html>
