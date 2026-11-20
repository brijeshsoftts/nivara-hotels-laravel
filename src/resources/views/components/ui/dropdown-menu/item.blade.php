@props([
    "inset" => false,
    "variant" => "default",
    "href" => null,
])

@php
    $classes = "group/dropdown-menu-item relative flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 " .
        ($inset ? "pl-7 " : "") .
        ($variant === "destructive" ? "text-destructive hover:bg-destructive/10 hover:text-destructive dark:hover:bg-destructive/20 *:[svg]:text-destructive" : "");
@endphp

@if($href)
    <a
        href="{{ $href }}"
        data-slot="dropdown-menu-item"
        data-inset="{{ $inset ? 'true' : 'false' }}"
        data-variant="{{ $variant }}"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </a>
@else
    <div
        data-slot="dropdown-menu-item"
        data-inset="{{ $inset ? 'true' : 'false' }}"
        data-variant="{{ $variant }}"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </div>
@endif
