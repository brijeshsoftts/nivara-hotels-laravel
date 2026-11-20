@props([
    "orientation" => "vertical",
])

<div
    data-slot="scroll-area"
    {{ $attributes->class(["relative overflow-auto max-h-full"]) }}
>
    <div
        data-slot="scroll-area-viewport"
        class="size-full rounded-[inherit] transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-1"
    >
        {{ $slot }}
    </div>
</div>
