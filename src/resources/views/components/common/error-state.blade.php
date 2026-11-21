@props([
    "heading" => "An error occurred",
    "description" => "Something went wrong. Please try again.",
    "icon" => "alert-circle",
])

<section
    role="alert"
    aria-live="assertive"
    {{ $attributes->class([
        "relative isolate flex min-h-[360px] w-full flex-col items-center justify-center overflow-hidden rounded-xl px-6 py-12 text-center"
    ]) }}
>
    <!-- Background grid -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div
            class="absolute inset-0"
            style="background-image: linear-gradient(to right, var(--border) 1px, transparent 1px), linear-gradient(to bottom, var(--border) 1px, transparent 1px); background-size: 40px 40px; mask-image: radial-gradient(ellipse 60% 50% at 50% 50%, #000 70%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 60% 50% at 50% 50%, #000 70%, transparent 100%); opacity: 0.35;"
        ></div>
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 size-72 rounded-full bg-destructive/5 blur-3xl"></div>
    </div>

    <!-- Icon box -->
    <div class="relative z-10 mb-4 flex items-center justify-center">
        @if(isset($illustration))
            {{ $illustration }}
        @else
            <div class="flex size-14 items-center justify-center rounded-xl border border-destructive/20 bg-background/90 text-destructive shadow-sm backdrop-blur-sm">
                <x-ui.icon :name="$icon" class="size-6 stroke-[1.7]" />
            </div>
        @endif
    </div>

    <!-- Heading and Description -->
    @if($heading || $description)
        <div class="relative z-10 max-w-md">
            @if($heading)
                <h2 class="mb-1 text-base font-semibold tracking-tight text-foreground">
                    {{ $heading }}
                </h2>
            @endif
            @if($description)
                <p class="text-sm leading-6 text-muted-foreground">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    <!-- Actions Slot -->
    @if($slot->isNotEmpty())
        <div class="relative z-10 mt-4 flex flex-wrap items-center justify-center gap-3">
            {{ $slot }}
        </div>
    @endif
</section>
