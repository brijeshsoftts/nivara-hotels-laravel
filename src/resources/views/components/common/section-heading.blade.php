@props([
    "title",
    "description" => null,
    "align" => "left",
])

<div {{ $attributes->class([
    "max-w-2xl space-y-4",
    "mx-auto text-center" => $align === "center",
]) }}>
    <h2 class="font-display text-4xl leading-tight font-medium tracking-tight text-balance sm:text-5xl">
        {{ $title }}
    </h2>
    <div
        aria-hidden="true"
        class="h-px w-24 bg-gradient-to-r from-transparent via-amber-500/70 to-transparent {{ $align === 'center' ? 'mx-auto' : '' }}"
    ></div>
    @if($description)
        <p class="text-base leading-relaxed text-muted-foreground sm:text-lg">
            {{ $description }}
        </p>
    @endif
    {{ $slot }}
</div>
