@props([
    "align" => "center",
    "side" => "bottom",
])

@php
    $alignClasses = [
        "start" => "left-0",
        "center" => "left-1/2 -translate-x-1/2",
        "end" => "right-0",
    ];
@endphp

<div
    x-show="open"
    x-cloak
    @click.outside="open = false"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    data-slot="popover-content"
    {{ $attributes->class([
        "absolute z-50 mt-1 flex w-72 flex-col gap-2.5 rounded-lg bg-popover p-2.5 text-sm text-popover-foreground shadow-md ring-1 ring-foreground/10 outline-hidden duration-100",
        $alignClasses[$align] ?? $alignClasses["center"]
    ]) }}
>
    {{ $slot }}
</div>
