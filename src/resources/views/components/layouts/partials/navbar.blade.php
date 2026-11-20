@php
$navLinks = [
['name' => 'Home', 'href' => route('home')],
['name' => 'About', 'href' => route('about')],
['name' => 'Rooms', 'href' => route('rooms.index')],
['name' => 'Contact', 'href' => route('contact')],
];
@endphp

<header class="sticky top-0 z-40 border-b border-border/80 bg-background/95 text-foreground backdrop-blur-md">

    <nav aria-label="Main navigation"
        class="container mx-auto flex min-h-18 w-full max-w-7xl items-center justify-between gap-5 px-4 sm:px-6 lg:px-8">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex items-center gap-3">

            <div class="relative flex h-12 w-10 items-end justify-center overflow-hidden rounded-t-full rounded-b-sm bg-secondary pb-1">
                <svg class="absolute -bottom-1 h-7 w-7 text-primary"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 22v-7" />
                    <path d="M12 15c-4 0-7-3-7-7 4 0 7 3 7 7Z" />
                    <path d="M12 15c0-4 3-7 7-7 0 4-3 7-7 7Z" />
                    <path d="M12 12c-3-3-3-7 0-10 3 3 3 7 0 10Z" />
                    <path d="M8 22h8" />
                </svg>
            </div>

            <div class="flex min-w-0 flex-col justify-center">
                <span class="font-heading text-[26px] leading-none tracking-wide text-primary">
                    Nivara
                </span>
                <span class="mt-1 font-sans text-[9px] font-medium tracking-[0.25em] uppercase text-primary/80">
                    Hotel & Resort
                </span>
            </div>

        </a>

        {{-- Desktop Navigation --}}
        <div class="hidden items-center gap-7 lg:flex xl:gap-9">

            <div class="hidden items-center gap-9 lg:flex">

                @foreach ($navLinks as $item)

                @php
                $active = request()->url() === $item['href'];
                @endphp

                <a href="{{ $item['href'] }}"
                    class="relative py-2 text-[13px] font-medium tracking-wide transition-colors duration-200
                        after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-left after:bg-primary after:transition-transform after:duration-300
                        {{ $active
                            ? 'text-foreground after:scale-x-100'
                            : 'text-muted-foreground after:scale-x-0 hover:text-foreground hover:after:scale-x-100' }}">

                    {{ $item['name'] }}

                </a>

                @endforeach

            </div>

        </div>

        {{-- Desktop Actions --}}
        <div class="hidden items-center gap-4 lg:flex">

            @auth

            <a href="{{ route('dashboard') }}"
                class="inline-flex h-11 items-center gap-2 rounded-lg border border-border px-5 font-semibold transition hover:bg-accent">

                <i data-lucide="layout-dashboard" class="size-4"></i>
                Dashboard

            </a>

            @else

            <a href="/login"
                class="min-h-11 content-center px-2 text-sm font-medium text-foreground transition-colors hover:text-primary">

                Log in

            </a>

            @endauth

            <a href="{{ route('rooms.index') }}"
                class="inline-flex h-11 items-center gap-2 rounded-lg bg-primary px-5 font-semibold text-primary-foreground transition hover:opacity-90">

                <i data-lucide="calendar" class="size-4"></i>
                Book now

            </a>

        </div>

        {{-- Mobile Menu Button --}}
        <button
            type="button"
            id="mobile-menu-toggle"
            aria-controls="public-mobile-navigation"
            aria-expanded="false"
            aria-label="Open navigation menu"
            class="inline-flex size-11 items-center justify-center rounded-lg border border-border lg:hidden">

            <i data-lucide="menu" id="menu-open-icon" class="size-5"></i>
            <i data-lucide="x" id="menu-close-icon" class="hidden size-5"></i>

        </button>

    </nav>

    {{-- Mobile Navigation --}}
    <div id="public-mobile-navigation"
        class="hidden overflow-hidden border-t border-border bg-background lg:hidden">

        <div class="container mx-auto max-w-7xl px-4 py-4 sm:px-6">

            <div class="flex flex-col">

                @foreach ($navLinks as $item)

                <a href="{{ $item['href'] }}"
                    class="flex min-h-12 items-center justify-between border-b border-border px-1 text-base font-medium transition-colors hover:text-primary">

                    {{ $item['name'] }}

                    <i data-lucide="arrow-up-right"
                        class="size-4 text-muted-foreground"></i>

                </a>

                @endforeach

            </div>

            @auth

            <a href="{{ route('dashboard') }}"
                class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary font-semibold text-primary-foreground">

                <i data-lucide="layout-dashboard" class="size-4"></i>
                Dashboard

            </a>

            @else

            <div class="grid grid-cols-2 gap-3 pt-4">

                <a href="/login"
                    class="flex h-11 items-center justify-center rounded-lg border border-border font-semibold">

                    Log in

                </a>

                <a href="{{ route('rooms.index') }}"
                    class="flex h-11 items-center justify-center gap-2 rounded-lg bg-primary font-semibold text-primary-foreground">

                    <i data-lucide="calendar" class="size-4"></i>
                    Book now

                </a>

            </div>

            @endauth

        </div>

    </div>

</header>