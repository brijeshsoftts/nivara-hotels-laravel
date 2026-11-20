@props([
    "value" => null,
])

<div
    role="option"
    data-slot="command-item"
    x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
    {{ $attributes->class([
        "relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
    ]) }}
>
    {{ $slot }}
</div>
