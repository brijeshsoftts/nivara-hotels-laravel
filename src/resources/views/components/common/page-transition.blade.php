@props([
    "duration" => "300ms",
])

<div
    {{ $attributes->class(["w-full min-w-0 transition-opacity duration-300 ease-out animate-in fade-in-0 slide-in-from-bottom-2"]) }}
>
    {{ $slot }}
</div>
