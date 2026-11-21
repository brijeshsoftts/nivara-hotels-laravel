@props([
    "title",
    "value",
    "icon" => "sparkles",
    "description" => null,
    "link" => null,
    "loading" => false,
])

@if($loading)
    <div class="rounded-xl border bg-card p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <x-ui.skeleton class="h-4 w-28" />
            <x-ui.skeleton class="size-8 rounded-lg" />
        </div>
        <x-ui.skeleton class="h-8 w-20 mt-4" />
        <x-ui.skeleton class="h-3 w-36 mt-2" />
    </div>
@else
    @php
        $cardMarkup = '
        <div class="rounded-xl border bg-card p-6 text-card-foreground shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">' . e($title) . '</span>
                <span class="grid size-9 place-items-center rounded-lg bg-primary/10 text-primary">
                    <x-ui.icon name="' . e($icon) . '" class="size-4" />
                </span>
            </div>
            <div class="mt-4 text-2xl font-bold tracking-tight text-foreground font-heading">
                ' . e($value) . '
            </div>
            ' . ($description ? '<p class="mt-1 text-xs text-muted-foreground">' . e($description) . '</p>' : '') . '
        </div>
        ';
    @endphp

    @if($link)
        <a href="{{ $link }}" {{ $attributes->class(["block outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-xl"]) }}>
            {!! $cardMarkup !!}
        </a>
    @else
        <div {{ $attributes }}>
            {!! $cardMarkup !!}
        </div>
    @endif
@endif
