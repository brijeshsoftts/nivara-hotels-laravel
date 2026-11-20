<li
    data-slot="breadcrumb-separator"
    role="presentation"
    aria-hidden="true"
    {{ $attributes->class(["[&>svg]:size-3.5"]) }}
>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
    @endif
</li>
