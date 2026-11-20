@props([
    "value",
])

<div
    role="option"
    data-slot="select-item"
    data-value="{{ $value }}"
    @click="select('{{ $value }}', $el.innerText.trim())"
    :data-selected="value == '{{ $value }}' ? 'true' : null"
    {{ $attributes->class([
        "relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
    ]) }}
>
    <span class="truncate">{{ $slot }}</span>
    <span
        class="absolute right-2 flex size-3.5 items-center justify-center"
        x-show="value == '{{ $value }}'"
    >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
    </span>
</div>
