@props([
    "inset" => false,
])

<div
    data-slot="dropdown-menu-sub-trigger"
    data-inset="{{ $inset ? 'true' : 'false' }}"
    @mouseenter="subOpen = true"
    @click="subOpen = !subOpen"
    {{ $attributes->class([
        "flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
        "pl-7" => $inset,
    ]) }}
>
    {{ $slot }}
    <svg class="ml-auto size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m9 18 6-6-6-6"/>
    </svg>
</div>
