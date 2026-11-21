@props([
    "eyebrow" => null,
    "title",
    "description" => null,
    "rightContent" => null,
])

<header {{ $attributes->class(["flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-end sm:justify-between"]) }}>
    <div class="min-w-0">
        @if($eyebrow)
            <p class="mb-1.5 flex items-center gap-2 text-xs font-medium text-primary">
                <span aria-hidden="true" class="h-px w-5 bg-primary/60"></span>
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="text-xl font-semibold tracking-[-0.015em] text-balance text-foreground sm:text-[22px] sm:leading-8">
            {{ $title }}
        </h1>

        @if($description)
            <p class="mt-1 max-w-xl text-[13px] leading-5 text-muted-foreground">
                {{ $description }}
            </p>
        @endif
    </div>

    @if($rightContent || isset($right))
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $rightContent ?? $right }}
        </div>
    @elseif($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</header>
