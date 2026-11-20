@props([
    "isActive" => false,
    "size" => "md",
    "href" => null,
])

@php
    $classes = "flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 text-sidebar-foreground outline-hidden ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 text-xs " .
        ($size === "sm" ? "text-xs" : "text-sm") . " " .
        ($isActive ? "bg-sidebar-accent text-sidebar-accent-foreground font-medium" : "");
@endphp

@if($href)
    <a
        href="{{ $href }}"
        data-slot="sidebar-menu-sub-button"
        data-size="{{ $size }}"
        data-active="{{ $isActive ? 'true' : 'false' }}"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="button"
        data-slot="sidebar-menu-sub-button"
        data-size="{{ $size }}"
        data-active="{{ $isActive ? 'true' : 'false' }}"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </button>
@endif
