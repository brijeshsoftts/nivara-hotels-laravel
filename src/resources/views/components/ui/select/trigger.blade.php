@props([
    "size" => "default",
])

<button
    type="button"
    role="combobox"
    data-slot="select-trigger"
    data-size="{{ $size }}"
    @click="open = !open"
    :aria-expanded="open ? 'true' : 'false'"
    {{ $attributes->class([
        "flex h-8 w-full items-center justify-between gap-1.5 rounded-lg border border-input bg-transparent px-2.5 py-1 text-sm transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30",
        "h-7 text-xs" => $size === "sm",
        "h-9" => $size === "lg",
    ]) }}
>
    <span class="truncate" x-text="label">
        {{ $slot->isNotEmpty() ? $slot : "" }}
    </span>
    <svg class="size-4 shrink-0 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m6 9 6 6 6-6"/>
    </svg>
</button>
