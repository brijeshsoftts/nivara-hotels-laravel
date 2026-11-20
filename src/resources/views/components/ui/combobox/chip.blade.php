@props([
    "showRemove" => true,
])

<span
    data-slot="combobox-chip"
    {{ $attributes->class([
        "inline-flex items-center gap-1 rounded-sm bg-muted px-1.5 py-0.5 text-xs font-medium text-foreground"
    ]) }}
>
    {{ $slot }}
    @if($showRemove)
        <button
            type="button"
            @click="$el.closest('[data-slot=combobox-chip]').remove()"
            class="opacity-60 hover:opacity-100"
        >
            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    @endif
</span>
