<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $title ?? 'Default Title' }}
    </title>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div x-data="{ sidebarOpen: false, sidebarCollapsed: false }" @keydown.escape.window="sidebarOpen = false" class="flex min-h-screen bg-background text-foreground">
        @include('components.layouts.partials.sidebar')

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border/80 bg-background/90 px-4 backdrop-blur-md sm:px-8">
                <button
                    type="button"
                    class="flex size-9 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent/50 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:hidden"
                    aria-label="Open dashboard navigation"
                    :aria-expanded="sidebarOpen"
                    @click="sidebarOpen = true">
                    <i data-lucide="menu" class="size-5"></i>
                </button>
                <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-sm">
                    <a href="{{ url('/dashboard') }}" class="text-muted-foreground transition-colors hover:text-foreground">Dashboard</a>
                    <i data-lucide="chevron-right" aria-hidden="true" class="size-3.5 shrink-0 text-muted-foreground/60"></i>
                    <span class="truncate font-medium text-foreground">{{ $title ?? 'Dashboard' }}</span>
                </nav>
            </header>

            <main class="mx-auto w-full max-w-7xl space-y-6 px-6 py-6 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>