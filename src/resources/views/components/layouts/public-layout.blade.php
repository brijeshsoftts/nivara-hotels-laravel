<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Nivara Hotel & Resort' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="min-h-screen flex flex-col bg-background text-foreground">

    @include('components.layouts.partials.navbar')

    <main class="min-w-0 flex-1" id="main-content" tabindex="-1">
        {{ $slot }}
    </main>
    @include('components.layouts.partials.footer')

</body>

</html>