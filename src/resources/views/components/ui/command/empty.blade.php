<div
    data-slot="command-empty"
    x-show="search.length > 0"
    {{ $attributes->class(["py-6 text-center text-sm text-muted-foreground"]) }}
>
    {{ $slot->isNotEmpty() ? $slot : "No results found." }}
</div>
