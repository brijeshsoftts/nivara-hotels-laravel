@props([
    "align" => "inline-start",
])

@php
    $alignClasses = [
        "inline-start" => "order-first pl-2 has-[>button]:ml-[-0.3rem] has-[>kbd]:ml-[-0.15rem]",
        "inline-end" => "order-last pr-2 has-[>button]:mr-[-0.3rem] has-[>kbd]:mr-[-0.15rem]",
        "block-start" => "order-first w-full justify-start px-2.5 pt-2 group-has-[>input]/input-group:pt-2 [.border-b]:pb-2",
        "block-end" => "order-last w-full justify-start px-2.5 pb-2 group-has-[>input]/input-group:pb-2 [.border-t]:pt-2",
    ];
@endphp

<div
    role="group"
    data-slot="input-group-addon"
    data-align="{{ $align }}"
    {{ $attributes->class([
        "flex items-center text-muted-foreground",
        $alignClasses[$align] ?? $alignClasses["inline-start"]
    ]) }}
>
    {{ $slot }}
</div>
