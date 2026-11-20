@props([
    "orientation" => "vertical",
])

<div
    data-slot="scroll-area-scrollbar"
    data-orientation="{{ $orientation }}"
    {{ $attributes->class([
        "flex touch-none p-px transition-colors select-none",
        "h-2.5 flex-col border-t border-t-transparent" => $orientation === "horizontal",
        "h-full w-2.5 border-l border-l-transparent" => $orientation === "vertical",
    ]) }}
>
    <div
        data-slot="scroll-area-thumb"
        class="relative flex-1 rounded-full bg-border"
    ></div>
</div>
