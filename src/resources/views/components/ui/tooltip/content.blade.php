@props([
    "side" => "top",
])

@php
    $sideClasses = [
        "top" => "bottom-full left-1/2 -translate-x-1/2 mb-1.5",
        "bottom" => "top-full left-1/2 -translate-x-1/2 mt-1.5",
        "left" => "right-full top-1/2 -translate-y-1/2 mr-1.5",
        "right" => "left-full top-1/2 -translate-y-1/2 ml-1.5",
    ];
@endphp

<div
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    data-slot="tooltip-content"
    {{ $attributes->class([
        "absolute z-50 pointer-events-none inline-flex w-fit max-w-xs items-center gap-1.5 rounded-md bg-foreground px-3 py-1.5 text-xs text-background whitespace-nowrap shadow-md",
        $sideClasses[$side] ?? $sideClasses["top"]
    ]) }}
>
    {{ $slot }}
</div>
