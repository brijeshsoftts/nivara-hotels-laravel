@props([
    "variant" => "card",
    "shape" => "default",
    "size" => "md",
    "href" => null,
    "type" => "button",
])

@php
    $baseClasses = "inline-flex cursor-pointer items-center justify-center text-sm font-medium whitespace-nowrap transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50";

    $variants = [
        "default" => "bg-primary text-primary-foreground shadow hover:bg-primary/90",
        "destructive" => "text-destructive-foreground bg-destructive shadow-sm hover:bg-destructive/90",
        "outline" => "border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground",
        "secondary" => "bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80",
        "ghost" => "hover:bg-accent hover:text-accent-foreground",
        "link" => "text-primary underline-offset-4 hover:underline",
        "card" => "border border-border bg-card text-muted-foreground hover:border-border/80 hover:text-foreground",
    ];

    $shapes = [
        "default" => "rounded-lg",
        "circle" => "rounded-full",
        "square" => "rounded-lg",
    ];

    $sizes = [
        "sm" => "h-8 w-8 [&_svg]:size-3.5",
        "md" => "h-9 w-9 [&_svg]:size-4",
        "lg" => "h-10 w-10 [&_svg]:size-5",
        "xl" => "h-12 w-12 [&_svg]:size-6",
    ];

    $classes = $baseClasses . " " . ($variants[$variant] ?? $variants["card"]) . " " . ($shapes[$shape] ?? $shapes["default"]) . " " . ($sizes[$size] ?? $sizes["md"]);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
