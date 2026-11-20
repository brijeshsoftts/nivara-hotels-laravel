@php
$hotelName = config('hotel.name', 'Nivara Hotel & Resort');
$hotelEmail = config('hotel.email') ?? "help@nivhar.com";
$hotelPhone = config('hotel.phone') ?? "+91 123 456 7890";
$hotelAddress = config('hotel.address') ?? "123, Main Street, Lucknow, India";

$navLinks = [
['name' => 'Home', 'href' => route('home')],
['name' => 'About', 'href' => route('about')],
['name' => 'Rooms', 'href' => route('rooms.index')],
['name' => 'Contact', 'href' => route('contact')],
];
@endphp

<footer class="w-full bg-secondary pt-16 pb-8 font-sans">

    <div class="container mx-auto px-6 md:px-12 lg:px-16">

        <div class="grid grid-cols-1 gap-12 pb-14 md:grid-cols-2 lg:grid-cols-4 lg:gap-8">

            {{-- Column 1: Brand --}}
            <div class="flex flex-col space-y-5">

                <a href="{{ route('home') }}" class="flex items-center gap-3">

                    <div class="relative flex h-12 w-10 items-end justify-center overflow-hidden rounded-t-full rounded-b-sm bg-secondary pb-1">

                        <i data-lucide="palmtree"
                            class="absolute -bottom-1 h-7 w-7 text-primary"></i>

                    </div>

                    <div class="flex flex-col justify-center">

                        <span class="font-heading text-[26px] leading-none tracking-wide text-primary">
                            Nivara
                        </span>

                        <span class="mt-1 text-[9px] font-medium tracking-[0.25em] uppercase text-primary/80">
                            Hotel & Resort
                        </span>

                    </div>

                </a>

                <p class="max-w-70 text-[14px] leading-relaxed text-foreground/70">

                    A comfortable hotel stay in Lucknow, with helpful service for
                    business and leisure guests.

                </p>

            </div>

            {{-- Column 2: Quick Links --}}
            <div class="flex flex-col">

                <h4 class="mb-5 text-[15px] font-bold tracking-wider text-foreground uppercase">
                    Quick Links
                </h4>

                <ul class="space-y-3.5">

                    @foreach ($navLinks as $link)

                    <li>
                        <a href="{{ $link['href'] }}"
                            class="text-[14px] font-medium text-foreground/70 transition-colors hover:text-primary">

                            {{ $link['name'] }}

                        </a>
                    </li>

                    @endforeach

                </ul>

            </div>

            {{-- Column 3: Contact --}}
            <div class="flex flex-col">

                <h4 class="mb-5 text-[15px] font-bold tracking-wider text-foreground uppercase">
                    Contact Us
                </h4>

                <ul class="space-y-4">

                    @if ($hotelAddress)

                    <li class="flex items-start gap-3 text-foreground/70">

                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">

                            <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>

                        </div>

                        <span class="text-[14px] leading-relaxed">
                            {{ $hotelAddress }}
                        </span>

                    </li>

                    @endif

                    @if ($hotelPhone)

                    <li class="flex items-center gap-3 text-foreground/70">

                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">

                            <i data-lucide="phone" class="h-3.5 w-3.5"></i>

                        </div>

                        <a href="tel:{{ $hotelPhone }}"
                            class="text-[14px] transition-colors hover:text-primary">

                            {{ $hotelPhone }}

                        </a>

                    </li>

                    @endif

                    @if ($hotelEmail)

                    <li class="flex items-center gap-3 text-foreground/70">

                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">

                            <i data-lucide="mail" class="h-3.5 w-3.5"></i>

                        </div>

                        <a href="mailto:{{ $hotelEmail }}"
                            class="break-all text-[14px] transition-colors hover:text-primary">

                            {{ $hotelEmail }}

                        </a>

                    </li>

                    @endif

                </ul>

            </div>

            {{-- Column 4: Newsletter --}}
            <div class="flex flex-col space-y-4">

                <h4 class="text-[15px] font-bold tracking-wider text-foreground uppercase">
                    Newsletter
                </h4>

                <form action="/newsletter/subscribe"
                    method="POST"
                    class="flex w-full max-w-[320px] flex-col gap-2">

                    @csrf

                    <label for="newsletter-email"
                        class="text-[13px] font-semibold text-foreground">

                        Subscribe to our newsletter

                    </label>

                    <div class="relative flex w-full items-center rounded-2xl border border-border bg-accent p-1">

                        <input
                            id="newsletter-email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="Your email address"
                            class="h-10 w-full rounded-xl border-0 bg-transparent pr-12 pl-3 text-sm text-foreground placeholder:text-foreground/50 focus:outline-none focus:ring-0">

                        <button
                            type="submit"
                            aria-label="Subscribe to newsletter"
                            class="absolute right-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm transition hover:opacity-90">

                            <i data-lucide="arrow-right" class="h-4 w-4"></i>

                        </button>

                    </div>

                    <p class="text-xs text-muted-foreground">
                        Subscribe to receive updates and special offers.
                    </p>

                    @error('email')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror

                </form>

            </div>

        </div>

        {{-- Separator --}}
        <div class="h-px w-full bg-border"></div>

        {{-- Copyright --}}
        <div class="flex flex-col items-center justify-between gap-4 pt-6 text-[12px] text-foreground/60 sm:flex-row md:text-[13px]">

            <span>
                © {{ date('Y') }} {{ $hotelName }}. All rights reserved.
            </span>

        </div>

    </div>

</footer>