@props([
    "value",
])

<div
    role="option"
    data-slot="combobox-item"
    data-value="{{ $value }}"
    x-show="!search || $el.innerText.toLowerCase().includes(search.toLowerCase())"
    @click="select('{{ $value }}')"
    {{ $attributes->class([
        "relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
    ]) }}
>
    <span class="truncate">{{ $slot }}</span>
    <span
        class="ml-auto flex size-4 items-center justify-center"
        x-show="value == '{{ $value }}'"
    >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
    </span>
</div>
