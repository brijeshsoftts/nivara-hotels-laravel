@props([
    "size" => "md",
    "iconOnly" => false,
    "href" => null,
])

@php
    $sizes = [
        "xs" => ["icon" => "size-5", "name" => "text-sm", "gap" => "gap-1.5"],
        "sm" => ["icon" => "size-6", "name" => "text-base", "gap" => "gap-2"],
        "md" => ["icon" => "size-8", "name" => "text-lg", "gap" => "gap-2.5"],
        "lg" => ["icon" => "size-10", "name" => "text-xl", "gap" => "gap-3"],
        "xl" => ["icon" => "size-12", "name" => "text-2xl", "gap" => "gap-3.5"],
    ];

    $s = $sizes[$size] ?? $sizes["md"];
    $uid = "logo_" . uniqid();
    $fill = $uid . "-fill";
    $sheen = $uid . "-sheen";
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->class(["inline-flex items-center", $s["gap"]]) }} @if($iconOnly) aria-label="Nivara Hotels" @endif>
@else
<span {{ $attributes->class(["inline-flex items-center", $s["gap"]]) }} @if($iconOnly) aria-label="Nivara Hotels" @endif>
@endif
    <svg
        aria-hidden="true"
        viewBox="0 0 40 40"
        class="shrink-0 drop-shadow-sm {{ $s['icon'] }}"
    >
        <defs>
            <linearGradient id="{{ $fill }}" x1="4" y1="2" x2="36" y2="38" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#F6DC8B" />
                <stop offset="0.5" stop-color="#D4A23A" />
                <stop offset="1" stop-color="#8F6516" />
            </linearGradient>
            <linearGradient id="{{ $sheen }}" x1="20" y1="0" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#fff" stop-opacity="0.38" />
                <stop offset="1" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#{{ $fill }})" />
        <rect width="40" height="40" rx="11" fill="url(#{{ $sheen }})" />
        <rect x="0.75" y="0.75" width="38.5" height="38.5" rx="10.25" fill="none" stroke="#fff" stroke-opacity="0.35" stroke-width="1.5" />
        <path d="M12 28V12h3.4l9.2 11.2V12H28v16h-3.4l-9.2-11.2V28H12Z" fill="#1A1408" />
        <path d="M30.5 5.5l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z" fill="#FFF4CC" />
    </svg>

    @if(!$iconOnly)
        <span class="flex flex-col">
            <span class="font-sans leading-none font-bold tracking-tight text-foreground {{ $s['name'] }}">
                Nivara
            </span>
        </span>
    @endif

@if($href)
</a>
@else
</span>
@endif
