@props([
    "placeholder" => "Search...",
])

<div class="relative flex items-center">
    <input
        type="text"
        x-model="search"
        @focus="open = true"
        placeholder="{{ $placeholder }}"
        data-slot="combobox-input"
        {{ $attributes->class([
            "flex h-8 w-full rounded-lg border border-input bg-transparent px-2.5 py-1 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
        ]) }}
    />
    <button
        type="button"
        @click="open = !open"
        class="absolute right-2 text-muted-foreground hover:text-foreground"
    >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>
</div>
