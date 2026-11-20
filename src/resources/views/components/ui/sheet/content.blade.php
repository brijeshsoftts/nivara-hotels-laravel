@props([
    "side" => "right",
    "showCloseButton" => true,
])

@php
    $sideClasses = [
        "top" => "inset-x-0 top-0 border-b",
        "bottom" => "inset-x-0 bottom-0 border-t",
        "left" => "inset-y-0 left-0 h-full w-3/4 border-r sm:max-w-sm",
        "right" => "inset-y-0 right-0 h-full w-3/4 border-l sm:max-w-sm",
    ];

    $enterStarts = [
        "top" => "-translate-y-full",
        "bottom" => "translate-y-full",
        "left" => "-translate-x-full",
        "right" => "translate-x-full",
    ];
@endphp

<template x-teleport="body">
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50"
    >
        <!-- Overlay -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            data-slot="sheet-overlay"
            class="fixed inset-0 isolate z-50 bg-black/50 duration-100 backdrop-blur-xs"
            @click="open = false"
        ></div>

        <!-- Sheet Panel -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="{{ $enterStarts[$side] ?? $enterStarts['right'] }}"
            x-transition:enter-end="translate-x-0 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 translate-y-0"
            x-transition:leave-end="{{ $enterStarts[$side] ?? $enterStarts['right'] }}"
            data-slot="sheet-content"
            data-side="{{ $side }}"
            {{ $attributes->class([
                "fixed z-50 flex flex-col gap-4 bg-background p-6 shadow-lg transition ease-in-out",
                $sideClasses[$side] ?? $sideClasses["right"]
            ]) }}
        >
            {{ $slot }}

            @if($showCloseButton)
                <button
                    type="button"
                    data-slot="sheet-close"
                    @click="open = false"
                    class="absolute top-4 right-4 inline-flex size-6 items-center justify-center rounded-md p-0 opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                    </svg>
                    <span class="sr-only">Close</span>
                </button>
            @endif
        </div>
    </div>
</template>
