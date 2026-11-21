@props([
    "features" => [
        "Breakfast Included",
        "Swimming Pool",
        "High Speed Wifi",
        "Spa & Wellness",
    ],
])

@php
    $items = array_merge($features, $features, $features);
@endphp

<div {{ $attributes->class(["relative flex w-full items-center overflow-hidden border-y border-border/20 bg-primary py-4 md:py-6"]) }}>
    <style>
        @keyframes infinite-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-70%); }
        }
        .animate-infinite-scroll {
            animation: infinite-scroll 25s linear infinite;
        }
        .animate-infinite-scroll:hover {
            animation-play-state: paused;
        }
    </style>

    <div class="animate-infinite-scroll flex w-max whitespace-nowrap">
        <div class="flex items-center">
            @foreach($items as $feature)
                <span class="px-8 font-heading text-xl font-medium tracking-wide text-primary-foreground md:px-12 md:text-2xl">
                    {{ $feature }}
                </span>
                <x-ui.icon name="sparkles" class="h-5 w-5 shrink-0 text-secondary md:h-6 md:w-6" />
            @endforeach
        </div>
    </div>
</div>
