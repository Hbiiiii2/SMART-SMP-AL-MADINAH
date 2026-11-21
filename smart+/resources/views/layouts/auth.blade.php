<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk' }} | SMART+</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 font-outfit antialiased">
    <div class="relative flex min-h-screen items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
        <!-- Background shapes -->
        <div class="absolute top-0 left-0 z-0 hidden lg:block">
            <img src="images/grid-01.svg" alt="" class="dark:hidden" />
            <img src="images/grid-01-dark.svg" alt="" class="hidden dark:block" />
        </div>
        <div class="absolute bottom-0 right-0 z-0 hidden lg:block">
            <img src="images/grid-02.svg" alt="" class="dark:hidden" />
            <img src="images/grid-02-dark.svg" alt="" class="hidden dark:block" />
        </div>

        <div class="z-1 w-full max-w-[440px]">
            @yield('content')
        </div>
    </div>
</body>

</html>
