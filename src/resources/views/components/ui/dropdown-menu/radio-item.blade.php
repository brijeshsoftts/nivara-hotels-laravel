@props([
    "value",
])

<div
    role="menuitemradio"
    data-slot="dropdown-menu-radio-item"
    @click="selectedValue = '{{ $value }}'"
    {{ $attributes->class([
        "group/dropdown-menu-radio-item relative flex cursor-pointer items-center gap-1.5 rounded-md py-1 pr-1.5 pl-7 text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground"
    ]) }}
>
    <span class="pointer-events-none absolute left-2 flex size-3.5 items-center justify-center">
        <span
            x-show="selectedValue === '{{ $value }}'"
            class="size-2 rounded-full bg-current"
        ></span>
    </span>
    {{ $slot }}
</div>
