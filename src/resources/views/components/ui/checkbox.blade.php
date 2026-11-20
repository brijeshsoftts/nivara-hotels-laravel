@props([
    "checked" => false,
    "name" => null,
    "value" => "1",
    "id" => null,
])

@php
    $id = $id ?? ($name ? $name . "_" . uniqid() : "chk_" . uniqid());
    $isChecked = $name ? old($name, $checked) : $checked;
@endphp

<label for="{{ $id }}" class="group/field inline-flex items-center gap-2 cursor-pointer select-none">
    <div class="relative flex items-center justify-center">
        <input
            type="checkbox"
            id="{{ $id }}"
            @if($name) name="{{ $name }}" @endif
            value="{{ $value }}"
            @checked($isChecked)
            {{ $attributes->whereStartsWith("wire:")->merge($attributes->whereStartsWith("x-")) }}
            class="peer sr-only"
        />
        <div
            data-slot="checkbox"
            {{ $attributes->whereDoesntStartWith(["wire:", "x-"])->class([
                "peer size-4 shrink-0 rounded-xs border border-input transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 aria-invalid:aria-checked:border-primary dark:bg-input/30 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40 peer-checked:border-primary peer-checked:bg-primary peer-checked:text-primary-foreground dark:peer-checked:bg-primary flex items-center justify-center"
            ]) }}
        >
            <svg
                data-slot="checkbox-indicator"
                class="size-3.5 hidden peer-checked:block text-current stroke-current"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M20 6 9 17l-5-5"/>
            </svg>
        </div>
    </div>
    @if($slot->isNotEmpty())
        <span class="text-sm font-medium leading-none text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $slot }}
        </span>
    @endif
</label>
