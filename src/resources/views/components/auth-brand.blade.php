@props(['compact' => false])

<a href="{{ route('home') }}" aria-label="Nivara Hotels &amp; Resorts" class="flex items-center gap-3 {{ $compact ? '' : 'relative z-10' }}">
    <div class="relative flex items-end justify-center overflow-hidden {{ $compact ? 'h-10 w-8' : 'h-12 w-10' }} rounded-t-full rounded-b-sm bg-secondary pb-1">
        <svg class="absolute -bottom-1 {{ $compact ? 'h-6 w-6' : 'h-7 w-7' }} text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M13 8c0-2.76-2.46-5-5.5-5S2 5.24 2 8h2l1-1 1 1h4" />
            <path d="M13 7.14A5.82 5.82 0 0 1 16.5 6c3.04 0 5.5 2.24 5.5 5h-3l-1-1-1 1h-3" />
            <path d="M5.89 9.71c-2.15 1.5-2.3 4.47-.39 6.29h2.8" />
            <path d="M17.5 15.71c2.15-1.5 2.3-4.47.39-6.29h-2.8" />
            <path d="m9 16 1 6" />
            <path d="m15 16-1 6" />
        </svg>
    </div>

    @if ($compact)
    <span class="font-heading text-2xl text-primary">Nivara</span>
    @else
    <div class="flex min-w-0 flex-col justify-center">
        <span class="font-heading text-[26px] leading-none tracking-wide text-background">Nivara</span>
        <span class="mt-1 font-sans text-[9px] font-medium tracking-[0.25em] uppercase text-background/80">HOTELS &amp; RESORTS</span>
    </div>
    @endif
</a>