@props([
    "placeholder" => "Type a command or search...",
])

<div class="flex items-center border-b px-3">
    <svg class="mr-2 size-4 shrink-0 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
    <input
        type="text"
        x-model="search"
        placeholder="{{ $placeholder }}"
        data-slot="command-input"
        {{ $attributes->class([
            "flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
        ]) }}
    />
</div>
