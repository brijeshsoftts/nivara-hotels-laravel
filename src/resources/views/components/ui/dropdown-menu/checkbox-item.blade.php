@props([
    "checked" => false,
])

<div
    role="menuitemcheckbox"
    aria-checked="{{ $checked ? 'true' : 'false' }}"
    data-slot="dropdown-menu-checkbox-item"
    {{ $attributes->class([
        "group/dropdown-menu-checkbox-item relative flex cursor-pointer items-center gap-1.5 rounded-md py-1 pr-1.5 pl-7 text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground"
    ]) }}
>
    <span class="pointer-events-none absolute left-2 flex size-3.5 items-center justify-center">
        @if($checked)
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
        @endif
    </span>
    {{ $slot }}
</div>
