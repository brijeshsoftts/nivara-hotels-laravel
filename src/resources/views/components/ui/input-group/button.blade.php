@props([
    "type" => "button",
    "variant" => "ghost",
    "size" => "xs",
])

@php
    $sizeClasses = [
        "xs" => "h-6 gap-1 rounded-md px-1.5 [&>svg:not([class*='size-'])]:size-3.5",
        "sm" => "",
        "icon-xs" => "size-6 rounded-md p-0 has-[>svg]:p-0",
        "icon-sm" => "size-8 p-0 has-[>svg]:p-0",
    ];
@endphp

<x-button
    type="{{ $type }}"
    variant="{{ $variant }}"
    size="{{ $size }}"
    {{ $attributes->class([
        "flex items-center gap-2 text-sm shadow-none",
        $sizeClasses[$size] ?? $sizeClasses["xs"]
    ]) }}
>
    {{ $slot }}
</x-button>
