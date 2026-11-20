<div
    data-slot="input-group"
    role="group"
    {{ $attributes->class([
        "group/input-group relative flex w-full items-center rounded-lg border border-input bg-transparent text-base transition-colors focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/50 has-[input:disabled]:cursor-not-allowed has-[input:disabled]:bg-input/50 has-[input:disabled]:opacity-50 has-[input[aria-invalid=true]]:border-destructive has-[input[aria-invalid=true]]:ring-3 has-[input[aria-invalid=true]]:ring-destructive/20 md:text-sm dark:bg-input/30 dark:has-[input:disabled]:bg-input/80 dark:has-[input[aria-invalid=true]]:border-destructive/50 dark:has-[input[aria-invalid=true]]:ring-destructive/40"
    ]) }}
>
    {{ $slot }}
</div>
