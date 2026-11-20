<textarea
    data-slot="input-group-control"
    {{ $attributes->class([
        "flex-1 resize-none border-0 bg-transparent py-2 px-2.5 shadow-none ring-0 focus-visible:ring-0 disabled:bg-transparent aria-invalid:ring-0 dark:bg-transparent dark:disabled:bg-transparent outline-none text-base md:text-sm text-foreground placeholder:text-muted-foreground"
    ]) }}
>{{ $slot }}</textarea>
