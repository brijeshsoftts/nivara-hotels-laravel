@props([
    "showCloseButton" => true,
])

<template x-teleport="body">
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Overlay -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            data-slot="dialog-overlay"
            class="fixed inset-0 isolate z-50 bg-black/50 duration-100 backdrop-blur-xs"
            @click="open = false"
        ></div>

        <!-- Popup -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            data-slot="dialog-content"
            {{ $attributes->class([
                "relative z-50 grid w-full max-w-[calc(100%-2rem)] gap-4 rounded-xl bg-background p-4 text-sm shadow-lg ring-1 ring-foreground/10 duration-100 sm:max-w-lg sm:p-6"
            ]) }}
        >
            {{ $slot }}

            @if($showCloseButton)
                <button
                    type="button"
                    data-slot="dialog-close"
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
