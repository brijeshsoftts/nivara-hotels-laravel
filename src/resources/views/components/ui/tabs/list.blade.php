@props([
    "variant" => "default",
])

@php
    $variants = [
        "default" => "bg-muted",
        "line" => "gap-1 bg-transparent",
    ];
@endphp

<div
    role="tablist"
    data-slot="tabs-list"
    data-variant="{{ $variant }}"
    {{ $attributes->class([
        "group/tabs-list inline-flex w-fit items-center justify-center rounded-lg p-[3px] text-muted-foreground group-data-horizontal/tabs:h-8 group-data-vertical/tabs:h-fit group-data-vertical/tabs:flex-col data-[variant=line]:rounded-none",
        $variants[$variant] ?? $variants["default"]
    ]) }}
>
    {{ $slot }}
</div>
