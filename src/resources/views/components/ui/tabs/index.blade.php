@props([
    "default" => null,
    "orientation" => "horizontal",
])

<div
    data-slot="tabs"
    data-orientation="{{ $orientation }}"
    x-data="{ activeTab: @js($default) }"
    {{ $attributes->class([
        "group/tabs flex gap-2",
        "flex-col data-horizontal:flex-col" => $orientation === "horizontal",
    ]) }}
>
    {{ $slot }}
</div>
