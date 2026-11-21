<div
    role="status"
    aria-live="polite"
    aria-label="Loading"
    {{ $attributes->class(["fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm"]) }}
>
    <x-ui.spinner class="size-8 text-primary" />
    <span class="sr-only">Loading...</span>
</div>
